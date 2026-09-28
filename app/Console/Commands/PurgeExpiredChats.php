<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enforces the per-plan chat retention promise advertised on billing
 * (plans.chat_history_days: 7/30/90). Sessions older than the owning
 * tenant's retention window are deleted (messages cascade); sessions
 * whose widget has no owner/plan fall back to the 7-day default.
 *
 * UU PDP storage-limitation basis; also ages out pre-encryption
 * plaintext rows (only new rows are encrypted at rest).
 */
class PurgeExpiredChats extends Command
{
    protected $signature = 'chat:purge {--dry-run : Report counts without deleting}';

    protected $description = 'Delete chat sessions older than the owning plan retention (chat_history_days)';

    /** Fallback when a widget has no owner or the owner has no plan. */
    public const DEFAULT_DAYS = 7;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $total = 0;

        // 1) Sessions owned by users with a plan: cutoff per plan.
        $planDays = DB::table('plans')
            ->whereNotNull('chat_history_days')
            ->pluck('chat_history_days', 'id');

        foreach ($planDays as $planId => $days) {
            $ids = DB::table('chat_sessions')
                ->join('widgets', 'widgets.id', '=', 'chat_sessions.widget_id')
                ->join('users', 'users.id', '=', 'widgets.user_id')
                ->where('users.plan_id', $planId)
                ->whereNotNull('widgets.user_id')
                ->where('chat_sessions.created_at', '<', now()->subDays((int) $days))
                ->pluck('chat_sessions.id');

            $total += $this->purgeIds($ids, $days, $dry);
        }

        // 2) Fallback: widget without owner, owner without plan, or a plan
        // that never promised a retention window -> 7 days default.
        $ids = DB::table('chat_sessions')
            ->leftJoin('widgets', 'widgets.id', '=', 'chat_sessions.widget_id')
            ->leftJoin('users', 'users.id', '=', 'widgets.user_id')
            ->leftJoin('plans', 'plans.id', '=', 'users.plan_id')
            ->where(function ($q) {
                $q->whereNull('widgets.user_id')
                    ->orWhereNull('users.plan_id')
                    ->orWhereNull('plans.chat_history_days');
            })
            ->where('chat_sessions.created_at', '<', now()->subDays(self::DEFAULT_DAYS))
            ->pluck('chat_sessions.id');

        $total += $this->purgeIds($ids, self::DEFAULT_DAYS, $dry);

        $verb = $dry ? '[dry-run] would delete' : 'deleted';
        $this->info("chat:purge {$verb} {$total} session(s).");
        Log::info('chat:purge', ['sessions_affected' => $total, 'dry_run' => $dry]);

        return Command::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection  $ids
     */
    protected function purgeIds($ids, int $days, bool $dry): int
    {
        $count = 0;

        foreach ($ids->chunk(500) as $chunk) {
            $chunkIds = $chunk->all();

            if ($dry) {
                $count += count($chunkIds);
                continue;
            }

            // Explicit delete (FK cascade also covers this, but keep the
            // message purge deterministic across engines).
            DB::table('chat_messages')->whereIn('session_id', $chunkIds)->delete();
            $count += ChatSession::whereIn('id', $chunkIds)->delete();
        }

        if ($count > 0) {
            $this->line(sprintf('  retention %dd: %s %d session(s)', $days, $dry ? 'would delete' : 'deleted', $count));
        }

        return $count;
    }
}
