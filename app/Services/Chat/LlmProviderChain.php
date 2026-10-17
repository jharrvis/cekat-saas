<?php

namespace App\Services\Chat;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Ordered list of LLM provider attempts for one chat request.
 *
 * Providers are configured by the admin (Admin > AI Models & Tiers):
 *  - SumoPod (OpenAI-compatible gateway, https://ai.sumopod.com) - used
 *    first when enabled and fully configured (key + model).
 *  - OpenRouter with the tier-mapped model - when enabled.
 *  - OpenRouter's Free Models Router (openrouter/free) - ALWAYS the
 *    final attempt, even when every provider toggle is off, so the
 *    chatbot never loses its last-resort route.
 *
 * Provider names and keys live only in admin surfaces and logs; nothing
 * here is ever exposed to visitors (model masking policy).
 */
class LlmProviderChain
{
    public const PROVIDER_OPENROUTER = 'openrouter';

    public const PROVIDER_SUMOPOD = 'sumopod';

    public const DEFAULT_SUMOPOD_BASE_URL = 'https://ai.sumopod.com';

    /**
     * Build the ordered attempt list for a tier-resolved model.
     *
     * @return array<int, array{provider: string, model: string}>
     */
    public function attemptsFor(string $tierModel): array
    {
        $attempts = [];

        if ($this->sumopodUsable()) {
            $attempts[] = [
                'provider' => self::PROVIDER_SUMOPOD,
                'model' => (string) Setting::get('ai_sumopod_model'),
            ];
        }

        if ($this->openrouterEnabled()) {
            $attempts[] = [
                'provider' => self::PROVIDER_OPENROUTER,
                'model' => $tierModel,
            ];
        }

        // Last resort, unconditionally: the Free Models Router. Skipped
        // only when the previous attempt already IS that exact call.
        $free = ['provider' => self::PROVIDER_OPENROUTER, 'model' => ModelResolver::FALLBACK_MODEL];
        if (end($attempts) !== $free) {
            $attempts[] = $free;
        }

        return $attempts;
    }

    public function openrouterEnabled(): bool
    {
        return filter_var(Setting::get('ai_openrouter_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function sumopodEnabled(): bool
    {
        return filter_var(Setting::get('ai_sumopod_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** SumoPod only counts when it can actually serve a request. */
    public function sumopodUsable(): bool
    {
        return $this->sumopodEnabled()
            && filled(Setting::get('ai_sumopod_api_key'))
            && filled(Setting::get('ai_sumopod_model'));
    }

    public function sumopodBaseUrl(): string
    {
        $base = (string) Setting::get('ai_sumopod_base_url', self::DEFAULT_SUMOPOD_BASE_URL);

        return rtrim($base !== '' ? $base : self::DEFAULT_SUMOPOD_BASE_URL, '/');
    }

    /**
     * Perform one provider attempt (OpenAI-compatible chat completion).
     *
     * @param  array{provider: string, model: string}  $attempt
     */
    public function post(array $attempt, array $messages, float $temperature = 0.7): Response
    {
        $payload = [
            'model' => $attempt['model'],
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => 1500,
        ];

        if ($attempt['provider'] === self::PROVIDER_SUMOPOD) {
            return Http::withHeaders([
                'Authorization' => 'Bearer '.(string) Setting::get('ai_sumopod_api_key'),
            ])->timeout(60)->post($this->sumopodBaseUrl().'/v1/chat/completions', $payload);
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Cekat SaaS',
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', $payload);
    }
}
