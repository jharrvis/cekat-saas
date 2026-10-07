<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountSuspended extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $type; // 'suspended' or 'banned'
    public ?string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $type, ?string $reason = null)
    {
        $this->user = $user;
        $this->type = $type;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->type === 'banned'
            ? __('auth.s.account_blocked_subject', [], $this->user->locale ?? 'id')
            : __('auth.s.account_suspended_subject', [], $this->user->locale ?? 'id');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-suspended',
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
