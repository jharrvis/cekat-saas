<?php

namespace App\Console\Commands;

use App\Mail\PlanExpired;
use App\Mail\PlanExpiringReminder;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
                Mail::to($user->email)->send(new PlanExpiringReminder($user, $daysLeft));
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
                Mail::to($user->email)->send(new PlanExpired($user, $oldPlanName));
                $this->info("Sent expiry notification to: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send expiry email to {$user->email}: {$e->getMessage()}");
            }

            // Downgrade to free plan
            $user->update([
                'plan_id' => $freePlan?->id,
                'plan_expires_at' => null,
                'monthly_message_used' => 0,
            ]);

            $this->info("Downgraded {$user->email} from {$oldPlanName} to Free Plan.");
        }

        $this->info("Processed {$users->count()} expired plans.");
    }
}
