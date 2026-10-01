<?php

use App\Models\Widget;
use Illuminate\Database\Migrations\Migration;

/**
 * The demo widget embedded on the landing page was reachable from any
 * origin (empty allowed_domains = fail-open), which allowed a one-request
 * cross-site exfiltration of its full system prompt. Lock it to the
 * application's own domains; the app host itself stays allowed via
 * DomainAccessService (local development included).
 */
return new class extends Migration
{
    public function up(): void
    {
        $widget = Widget::query()->where('slug', 'landing-page-default')->first();

        if (! $widget) {
            return;
        }

        $settings = $widget->settings ?? [];
        if (! is_array($settings)) {
            $settings = json_decode($settings, true) ?: [];
        }

        $settings['allowed_domains'] = 'cekat.biz.id, www.cekat.biz.id';

        $widget->settings = $settings;
        $widget->save();
    }

    public function down(): void
    {
        $widget = Widget::query()->where('slug', 'landing-page-default')->first();

        if (! $widget) {
            return;
        }

        $settings = $widget->settings ?? [];
        if (is_array($settings)) {
            unset($settings['allowed_domains']);
            $widget->settings = $settings;
            $widget->save();
        }
    }
};
