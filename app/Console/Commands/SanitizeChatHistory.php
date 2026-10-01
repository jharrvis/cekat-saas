<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Support\TextSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * One-time cleanup of stored chat text: new replies are sanitized
 * server-side in ChatOrchestrator before persisting, but rows written
 * before that still contain raw markdown (**bold**, ## headings, fences,
 * stray HTML) and possible mojibake in chat history, the admin inbox,
 * lead-email excerpts and summary prompts.
 */
class SanitizeChatHistory extends Command
{
    protected $signature = 'chat:sanitize-history {--dry-run : Report counts without writing}';

    protected $description = 'Strip markdown/HTML/mojibake from stored chat messages and summaries';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $messages = 0;
        $summaries = 0;

        ChatMessage::query()->chunkById(200, function ($rows) use ($dry, &$messages) {
            foreach ($rows as $m) {
                $clean = TextSanitizer::markdownToPlain((string) $m->content);

                if ($clean !== (string) $m->content) {
                    $messages++;
                    if (! $dry) {
                        $m->content = $clean;
                        $m->save();
                    }
                }
            }
        });

        ChatSession::query()->whereNotNull('summary')->chunkById(200, function ($rows) use ($dry, &$summaries) {
            foreach ($rows as $s) {
                $clean = TextSanitizer::markdownToPlain((string) $s->summary);

                if ($clean !== (string) $s->summary) {
                    $summaries++;
                    if (! $dry) {
                        $s->summary = $clean;
                        $s->save();
                    }
                }
            }
        });

        $verb = $dry ? '[dry-run] would clean' : 'cleaned';
        $this->info("chat:sanitize-history {$verb} {$messages} message(s), {$summaries} summar(y/ies).");
        Log::info('chat:sanitize-history', [
            'messages' => $messages,
            'summaries' => $summaries,
            'dry_run' => $dry,
        ]);

        return Command::SUCCESS;
    }
}
