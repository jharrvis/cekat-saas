<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Spike alert to a widget owner: repeated domain blocks or quota
 * denials inside a short window (see AlertWidgetAbuseSpike).
 */
class WidgetAbuseAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $widgetName,
        public string $type, // 'domain' | 'quota'
        public int $count,
        public int $windowMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.s.abuse_alert_subject', ['widget' => $this->widgetName], $this->user->locale ?? 'id'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.widget-abuse-alert',
        );
    }
}
