<?php

namespace App\Services\Billing;

use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Invoice generation for successful transactions.
 *
 * The invoice number is derived deterministically from the unique
 * order id (no extra storage): INV/<yyyy>/<mm>/<order_id>. PDFs are
 * rendered in the account owner's locale.
 */
class InvoiceService
{
    public static function number(Transaction $transaction): string
    {
        $date = $transaction->paid_at ?? $transaction->created_at;

        return 'INV/' . $date->format('Y/m') . '/' . $transaction->order_id;
    }

    public static function fileName(Transaction $transaction): string
    {
        return 'invoice-' . str_replace('/', '-', self::number($transaction)) . '.pdf';
    }

    /**
     * Render a PDF containing one invoice per transaction.
     *
     * @param  Collection<int, Transaction>  $transactions
     */
    public static function pdf(Collection $transactions, User $user): string
    {
        $locale = $user->locale ?? 'id';

        $pdf = Pdf::loadView('billing.invoice-pdf', [
            'transactions' => $transactions,
            'user' => $user,
            'locale' => $locale,
        ])->setPaper('a4');

        return $pdf->output();
    }
}
