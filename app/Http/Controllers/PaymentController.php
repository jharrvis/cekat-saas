<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set Midtrans configuration
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized');
        \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds');
    }

    /**
     * Create Midtrans Snap transaction
     */
    public function createTransaction(Plan $plan)
    {
        $user = auth()->user();

        // Check if user already has this plan
        if ($user->plan_id == $plan->id) {
            return back()->with('error', __('billing.already_on_plan'));
        }

        // Generate unique order ID
        $orderId = Transaction::generateOrderId();

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => $orderId,
            'amount' => $plan->price,
            'status' => 'pending',
        ]);

        // Midtrans parameters
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $plan->price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => $plan->slug,
                    'price' => (int) $plan->price,
                    'quantity' => 1,
                    'name' => 'Plan ' . $plan->name . ' - 1 Bulan',
                ],
            ],
            'callbacks' => [
                'finish' => route('payment.finish'),
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // Update transaction with snap token
            $transaction->update(['snap_token' => $snapToken]);

            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'order_id' => $orderId,
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error', [
                'message' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            $transaction->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle payment finish redirect
     */
    public function finish(Request $request)
    {
        $orderId = $request->get('order_id');
        $transaction = Transaction::where('order_id', $orderId)->first();

        if ($transaction) {
            // Check status from Midtrans
            try {
                $status = \Midtrans\Transaction::status($orderId);
                $this->handleTransactionStatus($transaction, $status);
            } catch (\Exception $e) {
                Log::error('Midtrans Status Check Error', ['message' => $e->getMessage()]);
            }
        }

        return redirect()->route('billing')->with(
            $transaction && $transaction->isSuccess() ? 'success' : 'info',
            $transaction && $transaction->isSuccess()
            ? 'Pembayaran berhasil! Plan Anda telah diaktifkan.'
            : 'Pembayaran sedang diproses. Status akan diupdate otomatis.'
        );
    }

    /**
     * Handle Midtrans webhook notification.
     *
     * Authenticity is proven by verifying the Midtrans signature_key
     * (sha512 of order_id + status_code + gross_amount + server key)
     * against the payload itself. The verified payload is then processed
     * directly — no SDK round-trip — so plan activation never depends on
     * the customer's browser returning to the finish page (T-03 / F-03).
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();
            $orderId = $payload['order_id'] ?? null;
            $statusCode = (string) ($payload['status_code'] ?? '');
            $grossAmount = (string) ($payload['gross_amount'] ?? '');
            $signatureKey = (string) ($payload['signature_key'] ?? '');

            $serverKey = (string) config('services.midtrans.server_key');
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if (! $orderId || ! hash_equals($expectedSignature, $signatureKey)) {
                Log::warning('Midtrans webhook rejected: invalid signature', ['order_id' => $orderId]);

                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }

            Log::info('Midtrans Notification', [
                'order_id' => $orderId,
                'status' => $payload['transaction_status'] ?? null,
                'payment_type' => $payload['payment_type'] ?? null,
                'fraud_status' => $payload['fraud_status'] ?? null,
            ]);

            $transaction = Transaction::where('order_id', $orderId)->first();

            if (! $transaction) {
                Log::error('Transaction not found', ['order_id' => $orderId]);

                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Update transaction with Midtrans response
            $transaction->update([
                'payment_type' => $payload['payment_type'] ?? $transaction->payment_type,
                'midtrans_response' => $payload,
            ]);

            $this->handleTransactionStatus($transaction, $payload);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', ['message' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Lightweight status probe for the billing page: lets the owner watch a
     * pending transaction flip to success without reloading (T-03).
     */
    public function transactionStatus(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->loadMissing('plan');

        return response()->json([
            'order_id' => $transaction->order_id,
            'status' => $transaction->status,
            'is_success' => $transaction->isSuccess(),
            'plan_name' => $transaction->plan?->name,
            'current_plan' => $request->user()->plan?->name,
            'plan_expires_at' => $request->user()->plan_expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Handle transaction status update
     */
    private function handleTransactionStatus(Transaction $transaction, $status)
    {
        $transactionStatus = $status->transaction_status ?? $status['transaction_status'] ?? null;
        $fraudStatus = $status->fraud_status ?? $status['fraud_status'] ?? null;

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            // Check fraud status for credit card
            if ($fraudStatus == 'accept' || $fraudStatus === null) {
                $this->activatePlan($transaction);
            } elseif ($fraudStatus == 'challenge') {
                $transaction->update(['status' => 'challenge']);
            } else {
                $transaction->update(['status' => 'failed']);
            }
        } elseif ($transactionStatus == 'pending') {
            $transaction->update(['status' => 'pending']);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $transaction->update(['status' => $transactionStatus == 'expire' ? 'expired' : 'failed']);
        }
    }

    /**
     * Activate user's plan after successful payment
     */
    private function activatePlan(Transaction $transaction)
    {
        // Idempotency (T-03): the webhook and the browser return flow can both
        // deliver the same settlement, and Midtrans retries notifications.
        // Lock the transaction row and activate at most once, so the plan
        // expiry is never extended twice and the success email is sent once.
        $transaction = \DB::transaction(function () use ($transaction) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            return ($locked && $locked->status === 'success' && $locked->paid_at) ? null : $locked;
        });

        if (! $transaction) {
            return;
        }

        $user = $transaction->user;
        $plan = $transaction->plan;

        // Update user's plan (extend from current expiry when renewing the same plan)
        $renewing = $user->plan_id === $plan->id
            && $user->plan_expires_at
            && $user->plan_expires_at->isFuture();
        $base = $renewing ? $user->plan_expires_at : now();

        // NOTE: monthly_message_used is intentionally NOT reset here (T-04).
        // Upgrading mid-cycle must not hand out a fresh quota; the counter is
        // reset only by the monthly schedule (ResetMonthlyQuota command).
        $user->update([
            'plan_id' => $plan->id,
            'plan_expires_at' => $base->copy()->addMonth(), // 1 month subscription
        ]);

        // Update transaction status
        $transaction->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Send payment success email
        try {
            \App\Services\Email\EmailSender::send(
                $user->email,
                new \App\Mail\PaymentSuccess($user, $transaction),
                'payment',
                ['user_id' => $user->id, 'transaction_id' => $transaction->id]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment success email', ['error' => $e->getMessage()]);
        }

        Log::info('Plan activated', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
        ]);
    }
}
