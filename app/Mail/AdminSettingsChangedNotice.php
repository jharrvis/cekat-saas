<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminSettingsChangedNotice extends Mailable
{
    use Queueable, SerializesModels;

    public ?User $changedBy;
    public string $group;
    public array $keys;

    public function __construct(?User $changedBy, string $group, array $keys = [])
    {
        $this->changedBy = $changedBy;
        $this->group = $group;
        $this->keys = $keys;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚙️ Setting sistem diubah: ' . ucfirst($this->group) . ' - Cekat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-settings-changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
