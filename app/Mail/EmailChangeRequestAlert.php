<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeRequestAlert extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $oldEmail;
    public string $newEmail;

    public function __construct(User $user, string $oldEmail, string $newEmail)
    {
        $this->user = $user;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permintaan Perubahan Email Akun - Cekat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
