<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Widget;
use App\Models\ChatSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLead extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Widget $widget;
    public ?ChatSession $session;
    public array $lead;

    public function __construct(User $user, Widget $widget, ?ChatSession $session, array $lead = [])
    {
        $this->user = $user;
        $this->widget = $widget;
        $this->session = $session;
        $this->lead = $lead;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lead Baru dari ' . $this->widget->name . ' - Cekat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-lead',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
