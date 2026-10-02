<?php

namespace App\Services\Email;

use App\Mail\CampaignEmail;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;

/**
 * Newsletter / announcement delivery engine. Recipients are snapshotted
 * from the segment when the campaign starts; sending happens in small
 * cursor-driven chunks (campaigns:send runs every minute) so bulk sends
 * survive closed browsers and the 60s queue-worker budget.
 */
class CampaignSender
{
    /**
     * Resolve a segment filter to an ordered list of user ids.
     *
     * Segment keys: verified (bool, default true), status
     * ('active' default = active or null, 'all', 'suspended', 'banned'),
     * plan_id (int|''), role (''|user|admin).
     *
     * @return array<int, int>
     */
    public static function resolveSegment(array $segment): array
    {
        $query = User::query()->orderBy('id');

        if ($segment['verified'] ?? true) {
            $query->whereNotNull('email_verified_at');
        }

        $status = $segment['status'] ?? 'active';

        if ($status === 'active') {
            $query->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($segment['plan_id'])) {
            $query->where('plan_id', (int) $segment['plan_id']);
        }

        if (! empty($segment['role'])) {
            $query->where('role', $segment['role']);
        }

        return $query->pluck('id')->all();
    }

    /**
     * Snapshot recipients and flip the campaign to "sending".
     * A stopped campaign resumes with its existing snapshot + cursor so
     * nobody is mailed twice. Returns false when the segment matches nobody.
     */
    public function start(EmailCampaign $campaign): bool
    {
        if ($campaign->status === 'stopped' && ! empty($campaign->recipients)) {
            $campaign->update(['status' => 'sending']);

            return true;
        }

        $ids = self::resolveSegment((array) $campaign->segment);

        if ($ids === []) {
            return false;
        }

        $campaign->update([
            'recipients' => $ids,
            'status' => 'sending',
            'cursor' => 0,
            'total_recipients' => count($ids),
            'sent_count' => 0,
            'failed_count' => 0,
            'started_at' => now(),
            'sent_at' => null,
        ]);

        return true;
    }

    /**
     * Send the next chunk of the campaign. Advances the cursor after
     * every recipient (durable against mid-chunk crashes) and marks the
     * campaign "sent" once the snapshot is exhausted.
     *
     * @return int number of recipients processed
     */
    public function sendChunk(EmailCampaign $campaign, int $limit = 15): int
    {
        $ids = (array) $campaign->recipients;
        $slice = array_slice($ids, $campaign->cursor, $limit);

        if ($slice === []) {
            $this->finish($campaign);

            return 0;
        }

        $users = User::whereIn('id', $slice)->get()->keyBy('id');

        foreach ($slice as $userId) {
            // Respect a stop issued by another process between chunks.
            if (EmailCampaign::whereKey($campaign->id)->value('status') !== 'sending') {
                return $limit; // stopped externally
            }

            $user = $users->get($userId);

            if ($user && $user->email) {
                [$subject, $body] = EmailTemplate::renderTokens(
                    $campaign->subject,
                    $campaign->body,
                    self::valuesFor($user),
                );

                try {
                    EmailSender::send(
                        $user->email,
                        new CampaignEmail(
                            $subject,
                            $body,
                            EmailCampaign::TYPE_LABELS[$campaign->type] ?? 'Cekat',
                            $campaign->name,
                        ),
                        'campaign-' . $campaign->type,
                        ['user_id' => $user->id, 'campaign_id' => $campaign->id],
                    );
                    $campaign->sent_count++;
                } catch (\Throwable) {
                    $campaign->failed_count++;
                }
            }

            $campaign->cursor++;
            $campaign->save();
        }

        if ($campaign->cursor >= count($ids)) {
            $this->finish($campaign);
        }

        return count($slice);
    }

    private function finish(EmailCampaign $campaign): void
    {
        if ($campaign->status === 'sending') {
            $campaign->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function valuesFor(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $user->plan?->name ?? 'Free',
        ];
    }
}
