<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChanged extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $ip;
    public string $changedVia;

    public function __construct(User $user, ?string $ip = null, string $changedVia = 'Pengaturan Akun')
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->changedVia = $changedVia;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('settings.s.password_changed_subject', [], $this->user->locale ?? 'id'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
