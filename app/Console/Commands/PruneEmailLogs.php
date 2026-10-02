<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Illuminate\Console\Command;

class PruneEmailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:prune {--days=90}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete outbound email log rows older than N days (rendered bodies carry PII)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $deleted = EmailLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} email log rows older than {$days} days.");

        return self::SUCCESS;
    }
}
