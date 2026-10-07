<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;

/**
 * T-16: anti-drift guard for the plans table (root cause of F-01).
 *
 * Compares every active plan against the canonical product invariants
 * (plan document Bab 2 — the same values UpdateExistingPlansSeeder writes)
 * and exits non-zero when production data has drifted, so the check can
 * run on a schedule or before a deploy:
 *
 *     php artisan plans:audit
 */
class PlansAudit extends Command
{
    protected $signature = 'plans:audit';

    protected $description = 'Audit tabel plans terhadap nilai kanonis produk; exit 1 bila ada drift';

    /**
     * Canonical invariants per slug. Mirrors UpdateExistingPlansSeeder —
     * when the product changes a plan, change BOTH and re-run the seeder.
     */
    private const EXPECTED = [
        'free' => [
            'price' => 0, 'can_export_leads' => false, 'ai_tier' => 'basic', 'chat_history_days' => 7,
        ],
        'starter' => [
            'can_export_leads' => false, 'ai_tier' => 'basic', 'chat_history_days' => 7,
            'features' => ['api_access' => false],
        ],
        'pro' => [
            'price' => 99000, 'can_export_leads' => true, 'ai_tier' => 'advanced', 'chat_history_days' => 30,
            'features' => ['api_access' => true],
        ],
        'business' => [
            'price' => 199000, 'can_export_leads' => true, 'ai_tier' => 'premium', 'chat_history_days' => 90,
            'features' => ['api_access' => true, 'white_label' => true],
        ],
    ];

    public function handle(): int
    {
        $violations = [];
        $rows = [];

        foreach (Plan::orderBy('sort_order')->get() as $plan) {
            $rows[] = [
                $plan->slug,
                number_format((float) $plan->price, 0, ',', '.'),
                $plan->can_export_leads ? 'ya' : 'tidak',
                $plan->ai_tier,
                $plan->chat_history_days,
            ];

            $expected = self::EXPECTED[$plan->slug] ?? null;
            if ($expected === null) {
                continue; // custom plans are the admin's prerogative
            }

            foreach ($expected as $field => $want) {
                if ($field === 'features') {
                    foreach ($want as $feature => $wantValue) {
                        $actual = $plan->features[$feature] ?? null;
                        if ($actual !== $wantValue) {
                            $violations[] = "{$plan->slug}.features.{$feature}: " . json_encode($actual) . ' (seharusnya ' . json_encode($wantValue) . ')';
                        }
                    }
                    continue;
                }

                $actual = $field === 'price' ? (int) $plan->price : $plan->{$field};
                if ($field === 'can_export_leads') {
                    $actual = (bool) $actual;
                }
                if ($actual !== $want) {
                    $violations[] = "{$plan->slug}.{$field}: " . json_encode($actual) . ' (seharusnya ' . json_encode($want) . ')';
                }
            }
        }

        $this->table(['Paket', 'Harga', 'Leads', 'Tier AI', 'Riwayat (hari)'], $rows);

        if ($violations !== []) {
            $this->error('DRIFT TERDETEKSI (' . count($violations) . '):');
            foreach ($violations as $violation) {
                $this->line(' - ' . $violation);
            }
            $this->line('Perbaiki dengan: php artisan db:seed --class=UpdateExistingPlansSeeder --force');

            return self::FAILURE;
        }

        $this->info('OK — semua paket sesuai nilai kanonis.');

        return self::SUCCESS;
    }
}
