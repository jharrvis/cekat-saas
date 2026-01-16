<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $oldPlanName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $oldPlanName)
    {
        $this->user = $user;
        $this->oldPlanName = $oldPlanName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Plan Anda Telah Berakhir - Cekat.ai',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expired',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
