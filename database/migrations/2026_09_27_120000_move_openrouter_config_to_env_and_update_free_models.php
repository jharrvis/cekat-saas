<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * OpenRouter hygiene pass:
 * - API key moves to .env only (remove the stored DB copy),
 * - retired free model ids are replaced by the Free Models Router
 *   (openrouter/free) in settings, plan limits, widget config and the
 *   admin model catalogue.
 */
return new class extends Migration {
    private const FREE_REPLACEMENT = 'openrouter/free';

    /** Model ids retired by OpenRouter (verified 2026-09-27). */
    private const RETIRED_MODELS = [
        'nvidia/nemotron-3-nano-30b-a3b:free',
        'nvidia/llama-3.1-nemotron-70b-instruct:free',
        'deepseek/deepseek-r1:free',
        'google/gemini-2.0-flash-exp:free',
        'anthropic/claude-3.5-haiku',
        'anthropic/claude-3.5-sonnet',
        'anthropic/claude-3-opus',
        'google/gemini-pro-1.5',
        'xiaomi/mimo-v2-flash:free',
        'mistralai/devstral-2512:free',
    ];

    public function up(): void
    {
        // 1) API key now lives only in .env - drop the stored copy
        DB::table('settings')->where('key', 'openrouter_api_key')->delete();
        Cache::forget('setting.openrouter_api_key');

        // 2) Default model setting: only rewrite dead ids, keep working ones
        $defaultRow = DB::table('settings')->where('key', 'default_ai_model')->first();
        if ($defaultRow && in_array($defaultRow->value, self::RETIRED_MODELS, true)) {
            DB::table('settings')->where('key', 'default_ai_model')->update(['value' => self::FREE_REPLACEMENT]);
            Cache::forget('setting.default_ai_model');
        }

        // 3) AI tier mapping: only the retired basic-tier model is replaced
        $tierRow = DB::table('settings')->where('key', 'ai_tier_mapping')->first();
        if ($tierRow) {
            $mapping = json_decode($tierRow->value, true) ?: [];

            if (isset($mapping['basic']) && in_array($mapping['basic'], self::RETIRED_MODELS, true)) {
                $mapping['basic'] = self::FREE_REPLACEMENT;
                DB::table('settings')->where('key', 'ai_tier_mapping')->update(['value' => json_encode($mapping)]);
                Cache::forget('setting.ai_tier_mapping');
            }
        }

        // 4) Plan allowed_models lists
        foreach (DB::table('plans')->get() as $plan) {
            $models = json_decode($plan->allowed_models ?? '[]', true);

            if (! is_array($models) || $models === []) {
                continue;
            }

            $updated = array_values(array_unique(array_map(
                fn ($id) => in_array($id, self::RETIRED_MODELS, true) ? self::FREE_REPLACEMENT : $id,
                $models,
            )));

            if ($updated !== $models) {
                DB::table('plans')->where('id', $plan->id)->update(['allowed_models' => json_encode($updated)]);
            }
        }

        // 5) Widget model settings (landing/demo widgets store a model directly)
        foreach (DB::table('widgets')->get() as $widget) {
            $settings = json_decode($widget->settings ?? 'null', true);

            if (! is_array($settings) || ! isset($settings['model'])) {
                continue;
            }

            if (in_array($settings['model'], self::RETIRED_MODELS, true)) {
                $settings['model'] = self::FREE_REPLACEMENT;
                DB::table('widgets')->where('id', $widget->id)->update(['settings' => json_encode($settings)]);
            }
        }

        // 6) Admin model catalogue: retire dead entries, ensure router exists
        DB::table('llm_models')->whereIn('model_id', self::RETIRED_MODELS)->update(['is_active' => false]);

        if (! DB::table('llm_models')->where('model_id', self::FREE_REPLACEMENT)->exists()) {
            DB::table('llm_models')->insert([
                'model_id' => self::FREE_REPLACEMENT,
                'name' => 'Free Models Router',
                'provider' => 'OpenRouter',
                'description' => 'Router that picks an available free model automatically',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 200000,
                'allowed_tiers' => json_encode(['starter', 'pro', 'business']),
                'is_active' => true,
                'popularity' => 95,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The removed API key cannot (and must not) be restored here:
        // set OPENROUTER_API_KEY in .env instead.
        DB::table('settings')->updateOrInsert(
            ['key' => 'openrouter_api_key'],
            ['value' => '', 'type' => 'string', 'group' => 'api', 'description' => 'OpenRouter API Key', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
        );
    }
};
