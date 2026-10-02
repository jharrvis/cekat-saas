<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Delivery vehicle for admin-authored content (newsletter, announcement,
 * template test sends). The body is plain HTML coming from the database -
 * it is echoed raw into emails/campaign.blade.php, never compiled as Blade,
 * so stored content cannot execute code.
 */
class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $bodyHtml,
        public string $categoryLabel = 'Cekat',
        public string $title = 'Cekat',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: [
                'body' => $this->bodyHtml,
                'category' => $this->categoryLabel,
                'title' => $this->title,
            ],
        );
    }
}
