<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtp extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $code;

    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
        // T-12: render in the recipient's language.
        $this->locale = $user->locale ?? 'id';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.otp_subject', [], $this->user->locale ?? 'id'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
