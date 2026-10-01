<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Static guard: plan tier rules must only live in PlanLimitService (data from
 * the plans table). Enforcement/display code may not read tier columns
 * directly, write dropped user quota fields, or branch on plan slugs.
 */
class PlanLimitRegressionTest extends TestCase
{
    private const BANNED = '/\b(max_widgets|max_messages_per_month|max_documents|max_file_size_mb|max_faqs|can_use_whatsapp|can_export_leads|ai_tier|plan_tier|monthly_message_quota)\b/';

    /** Branching on plan slug/name with tier literals instead of the service. */
    private const SLUG_BRANCH = "/\\\$plan->slug[\\s\\S]{0,150}?'(starter|free|pro|professional|business|enterprise)'/";

    private const ALLOWED = [
        'app/Livewire/Admin/PlanManager.php',
        'resources/views/livewire/admin/plan-manager.blade.php',
        'app/Services/Billing/PlanLimitService.php',
        // Reads no plan columns; 'ai_tier' appears only as a log payload label.
        'app/Services/Chat/ModelResolver.php',
    ];

    private function scan(array $dirs, string $pattern): array
    {
        $violations = [];
        $base = rtrim(str_replace('\\', '/', base_path()), '/') . '/';

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            foreach (File::allFiles($dir) as $file) {
                $relative = str_replace('\\', '/', $file->getPathname());
                $relative = str_replace($base, '', $relative);

                if (in_array($relative, self::ALLOWED, true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if (preg_match_all($pattern, $contents, $matches)) {
                    $violations[] = $relative . ': ' . implode(', ', array_unique($matches[0]));
                }
            }
        }

        return $violations;
    }

    private function enforcementDirs(): array
    {
        return [
            app_path('Http/Controllers'),
            app_path('Http/Middleware'),
            app_path('Livewire'),
            app_path('Services'),
            app_path('Console'),
        ];
    }

    public function test_backend_enforcement_code_does_not_read_plan_columns_directly(): void
    {
        $violations = $this->scan($this->enforcementDirs(), self::BANNED);

        $this->assertSame(
            [],
            $violations,
            "Direct plan limit reads found:\n" . implode("\n", $violations)
        );
    }

    public function test_no_code_branches_on_plan_slug_tiers(): void
    {
        $violations = $this->scan($this->enforcementDirs(), self::SLUG_BRANCH);

        $this->assertSame(
            [],
            $violations,
            "Plan slug tier branching found (use PlanLimitService):\n" . implode("\n", $violations)
        );
    }

    public function test_views_do_not_read_plan_columns_directly(): void
    {
        $violations = $this->scan([resource_path('views')], self::BANNED);

        $this->assertSame(
            [],
            $violations,
            "Direct plan limit reads found in views:\n" . implode("\n", $violations)
        );
    }
}
