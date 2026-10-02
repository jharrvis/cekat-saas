<?php

namespace App\Console\Commands;

use App\Mail\PlanExpired;
use App\Mail\PlanExpiringReminder;
use App\Models\Plan;
use App\Models\User;
use App\Services\Email\EmailSender;
use Illuminate\Console\Command;

class CheckPlanExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring/expired plans and send reminders or downgrade';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking plan expiry...');

        // Get Free plan for downgrade
        $freePlan = Plan::where('price', 0)->first();

        // 1. Send 7-day reminder
        $this->sendReminders(7);

        // 2. Send 3-day reminder
        $this->sendReminders(3);

        // 3. Send 1-day reminder
        $this->sendReminders(1);

        // 4. Process expired plans (downgrade to free)
        $this->processExpiredPlans($freePlan);

        $this->info('Plan expiry check completed.');

        return Command::SUCCESS;
    }

    /**
     * Send reminder emails to users whose plan expires in X days.
     */
    private function sendReminders(int $daysLeft): void
    {
        $targetDate = now()->addDays($daysLeft)->startOfDay();

        $users = User::whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', $targetDate)
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->whereHas('plan', function ($q) {
                $q->where('price', '>', 0);
            })
            ->get();

        foreach ($users as $user) {
            try {
                EmailSender::send($user->email, new PlanExpiringReminder($user, $daysLeft), 'plan-expiring', ['user_id' => $user->id]);
                $this->info("Sent {$daysLeft}-day reminder to: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$users->count()} users for {$daysLeft}-day reminder.");
    }

    /**
     * Downgrade users with expired plans to free plan.
     */
    private function processExpiredPlans(?Plan $freePlan): void
    {
        $users = User::whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->whereHas('plan', function ($q) {
                $q->where('price', '>', 0);
            })
            ->get();

        foreach ($users as $user) {
            $oldPlanName = $user->plan->name ?? 'Premium';

            try {
                // Send expired notification
                EmailSender::send($user->email, new PlanExpired($user, $oldPlanName), 'plan-expired', ['user_id' => $user->id]);
                $this->info("Sent expiry notification to: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send expiry email to {$user->email}: {$e->getMessage()}");
            }

            // Downgrade to free plan + deactivate all channels
            \App\Services\Billing\PlanExpiryService::downgrade($user);

            $this->info("Downgraded {$user->email} from {$oldPlanName} to Free Plan.");
        }

        $this->info("Processed {$users->count()} expired plans.");
    }
}
