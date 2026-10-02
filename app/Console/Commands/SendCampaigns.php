<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\Email\CampaignSender;
use Illuminate\Console\Command;

class SendCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:send {--chunk=15} {--budget=40}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send one chunk of every campaign currently in "sending" status';

    /**
     * Execute the console command.
     */
    public function handle(CampaignSender $sender): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $deadline = microtime(true) + max(5, (int) $this->option('budget'));

        $campaigns = EmailCampaign::where('status', 'sending')->orderBy('id')->get();

        foreach ($campaigns as $campaign) {
            if (microtime(true) >= $deadline) {
                $this->info('Time budget reached; remaining campaigns resume next run.');
                break;
            }

            $processed = $sender->sendChunk($campaign, $chunk);
            $campaign->refresh();

            $this->info("Campaign #{$campaign->id} ({$campaign->type}): {$processed} processed, "
                . "status {$campaign->status}, {$campaign->sent_count}/{$campaign->total_recipients} sent.");
        }

        return self::SUCCESS;
    }
}
