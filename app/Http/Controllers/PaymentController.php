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
            return back()->with('error', 'Anda sudah menggunakan plan ini.');
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
     * Handle Midtrans webhook notification
     */
    public function webhook(Request $request)
    {
        try {
            $notification = new \Midtrans\Notification();

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $paymentType = $notification->payment_type;
            $fraudStatus = $notification->fraud_status ?? null;

            Log::info('Midtrans Notification', [
                'order_id' => $orderId,
                'status' => $transactionStatus,
                'payment_type' => $paymentType,
                'fraud_status' => $fraudStatus,
            ]);

            $transaction = Transaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                Log::error('Transaction not found', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Update transaction with Midtrans response
            $transaction->update([
                'payment_type' => $paymentType,
                'midtrans_response' => $request->all(),
            ]);

            $this->handleTransactionStatus($transaction, $notification);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
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
