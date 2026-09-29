<?php

namespace App\Mail;

use App\Http\Controllers\SettingsController;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeConfirm extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $oldEmail;
    public string $newEmail;
    public string $confirmationUrl;

    public function __construct(User $user, string $oldEmail, string $newEmail)
    {
        $this->user = $user;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
        $this->confirmationUrl = SettingsController::confirmationUrl($user);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Konfirmasi Perubahan Email - Cekat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-confirm',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
