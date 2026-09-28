<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Static guard: plan tier rules must only live in PlanLimitService (data from
 * the plans table). Enforcement/display code may not read tier columns directly.
 */
class PlanLimitRegressionTest extends TestCase
{
    private const BANNED = '/\b(max_widgets|max_messages_per_month|max_documents|max_file_size_mb|max_faqs|can_use_whatsapp|can_export_leads|ai_tier)\b/';

    private const ALLOWED = [
        'app/Livewire/Admin/PlanManager.php',
        'resources/views/livewire/admin/plan-manager.blade.php',
    ];

    private function scan(array $dirs): array
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

                if (preg_match_all(self::BANNED, $contents, $matches)) {
                    $violations[] = $relative . ': ' . implode(', ', array_unique($matches[0]));
                }
            }
        }

        return $violations;
    }

    public function test_backend_enforcement_code_does_not_read_plan_columns_directly(): void
    {
        $violations = $this->scan([
            app_path('Http/Controllers'),
            app_path('Http/Middleware'),
            app_path('Livewire'),
        ]);

        $this->assertSame(
            [],
            $violations,
            "Direct plan limit reads found:\n" . implode("\n", $violations)
        );
    }

    public function test_views_do_not_read_plan_columns_directly(): void
    {
        $violations = $this->scan([resource_path('views')]);

        $this->assertSame(
            [],
            $violations,
            "Direct plan limit reads found in views:\n" . implode("\n", $violations)
        );
    }
}
