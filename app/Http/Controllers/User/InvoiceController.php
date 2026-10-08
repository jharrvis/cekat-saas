<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * Download the invoice PDF for one of the user's own successful
     * transactions. Anything else answers 404 (no existence leak).
     */
    public function download(Request $request, Transaction $transaction): Response
    {
        $user = $request->user();

        abort_unless($transaction->user_id === $user->id && $transaction->status === 'success', 404);

        $pdf = InvoiceService::pdf(collect([$transaction->load('plan')]), $user);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . InvoiceService::fileName($transaction) . '"',
        ]);
    }

    /**
     * Download one combined PDF with an invoice per successful
     * transaction ("Unduh Semua Invoice" quick action).
     */
    public function downloadAll(Request $request): Response
    {
        $user = $request->user();

        $transactions = $user->transactions()
            ->where('status', 'success')
            ->with('plan')
            ->oldest('paid_at')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->route('billing')
                ->with('error', __('billing.s.invoice_belum_tersedia'));
        }

        $pdf = InvoiceService::pdf($transactions, $user);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="cekat-invoices.pdf"',
        ]);
    }
}
