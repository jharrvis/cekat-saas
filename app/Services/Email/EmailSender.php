<?php

namespace App\Services\Email;

use App\Models\EmailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Single choke point for every outbound email in the app. Sends the
 * mailable, writes an email_logs row (sent or failed) and rethrows so
 * each call site keeps its own error handling semantics. Logging must
 * never mask a transport error, so log writes are swallowed on failure.
 */
class EmailSender
{
    /**
     * @param  string|array<int, string>  $to
     * @param  array<string, mixed>  $meta  optional keys: user_id, widget_id, template_id, campaign_id
     * @return bool true when the mail transport accepted the message
     */
    public static function send(string|array $to, Mailable $mailable, string $category, array $meta = []): bool
    {
        $recipient = implode(', ', (array) $to);
        $campaignId = isset($meta['campaign_id']) ? (int) $meta['campaign_id'] : null;

        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            static::store([
                'category' => $category,
                'mailable' => $mailable::class,
                'recipient' => $recipient,
                'subject' => static::subjectOf($mailable),
                'status' => 'failed',
                'error' => $e->getMessage(),
                'meta' => $meta ?: null,
                'campaign_id' => $campaignId,
            ]);

            Log::error('Outbound email failed', [
                'category' => $category,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        static::store([
            'category' => $category,
            'mailable' => $mailable::class,
            'recipient' => $recipient,
            'subject' => static::subjectOf($mailable),
            'status' => 'sent',
            'error' => null,
            // T-14: OTP emails carry a live credential — never persist their
            // body in the admin-visible email log. Subject/status stay logged.
            'body' => $category === 'otp'
                ? '[konten disamarkan — email OTP]'
                : static::renderBody($mailable),
            'meta' => $meta ?: null,
            'campaign_id' => $campaignId,
        ]);

        return true;
    }

    private static function subjectOf(Mailable $mailable): ?string
    {
        if ($mailable->subject) {
            return $mailable->subject;
        }

        try {
            return $mailable->envelope()->subject;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function renderBody(Mailable $mailable): ?string
    {
        try {
            return $mailable->render();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function store(array $attributes): void
    {
        try {
            EmailLog::create($attributes);
        } catch (\Throwable $e) {
            Log::warning('email_logs write failed', ['error' => $e->getMessage()]);
        }
    }
}
