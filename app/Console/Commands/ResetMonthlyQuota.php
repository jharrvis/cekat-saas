<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quota:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly message quota for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = User::query()->update(['monthly_message_used' => 0]);

        $this->info("Monthly quota reset for {$count} users.");

        // Log the reset
        \Log::info('Monthly quota reset executed', ['users_affected' => $count]);

        return Command::SUCCESS;
    }
}
