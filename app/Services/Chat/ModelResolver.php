<?php

namespace App\Services\Chat;

use App\Models\Setting;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the LLM model for a chat request from the owner's plan AI tier.
 *
 * The AI Agent does NOT determine the model; the plan's AI tier does
 * (LLM abstraction). Extracted verbatim from Api\ChatController.
 */
class ModelResolver
{
    public function forWidget(?Widget $widget): string
    {
        $defaultModel = config('services.openrouter.default_model', 'nvidia/nemotron-3-nano-30b-a3b:free');

        if (! $widget) {
            return $defaultModel;
        }

        // Special handling for landing page widget - use direct model from settings
        if ($widget->slug === QuotaService::LANDING_SLUG) {
            $settings = $widget->settings ?? [];
            $model = $settings['model'] ?? $defaultModel;
            Log::info('Landing Page Model Selection', ['model' => $model]);

            return $model;
        }

        if (! $widget->user) {
            return $defaultModel;
        }

        $user = $widget->user;
        $plan = $user->plan;

        if (! $plan) {
            return $defaultModel;
        }

        $aiTier = $plan->ai_tier ?? 'basic';

        // Setting::get may return array (if type=json) or string
        $mappingData = Setting::get('ai_tier_mapping');

        if ($mappingData) {
            $mapping = is_array($mappingData) ? $mappingData : json_decode($mappingData, true);

            if (is_array($mapping) && isset($mapping[$aiTier])) {
                Log::info('AI Tier Model Selection', [
                    'user_id' => $user->id,
                    'plan' => $plan->name ?? 'unknown',
                    'ai_tier' => $aiTier,
                    'model' => $mapping[$aiTier],
                ]);

                return $mapping[$aiTier];
            }
        }

        // Fallback mapping if settings not configured
        $defaultMapping = [
            'basic' => 'nvidia/nemotron-3-nano-30b-a3b:free',
            'standard' => 'openai/gpt-4o-mini',
            'advanced' => 'openai/gpt-4o-mini',
            'premium' => 'openai/gpt-4o-mini',
        ];

        Log::info('AI Tier Model Selection (fallback)', [
            'user_id' => $user->id,
            'plan' => $plan->name ?? 'unknown',
            'ai_tier' => $aiTier,
            'model' => $defaultMapping[$aiTier] ?? $defaultModel,
        ]);

        return $defaultMapping[$aiTier] ?? $defaultModel;
    }
}
