<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Transaction $transaction;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Transaction $transaction)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        // T-12: render in the recipient's language.
        $this->locale = $user->locale ?? 'id';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ ' . __('emails.payment_subject', ['plan' => $this->transaction->plan->name ?? ''], $this->user->locale ?? 'id'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-success',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Attach the invoice PDF; a rendering failure must never block
        // the success email itself.
        try {
            $transaction = $this->transaction->loadMissing('plan');
            $pdf = \App\Services\Billing\InvoiceService::pdf(collect([$transaction]), $this->user);

            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(
                    fn () => $pdf,
                    \App\Services\Billing\InvoiceService::fileName($transaction)
                )->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
