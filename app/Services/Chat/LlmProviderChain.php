<?php

namespace App\Services\Chat;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Ordered list of LLM provider attempts for one chat request.
 *
 * The tier mapping decides the primary provider: a mapping value of
 * "sumopod:<model-id>" (chosen in Admin > AI Models & Tiers) targets
 * SumoPod; any other value is an OpenRouter model id. When the primary
 * attempt's provider is unavailable or fails, the chain crosses over
 * to the other provider's configured model, and OpenRouter's Free
 * Models Router (openrouter/free) is ALWAYS the final attempt - even
 * when every provider toggle is off, so the chatbot never loses its
 * last-resort route.
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
     * Split a tier-resolved target into provider + model. A "sumopod:"
     * prefix marks a SumoPod model; anything else is an OpenRouter id.
     *
     * @return array{0: string, 1: string}
     */
    public function parseTarget(string $tierTarget): array
    {
        if (str_starts_with($tierTarget, self::PROVIDER_SUMOPOD.':')) {
            return [self::PROVIDER_SUMOPOD, substr($tierTarget, strlen(self::PROVIDER_SUMOPOD) + 1)];
        }

        return [self::PROVIDER_OPENROUTER, $tierTarget];
    }

    /**
     * Build the ordered attempt list for a tier-resolved target.
     *
     * @return array<int, array{provider: string, model: string}>
     */
    public function attemptsFor(string $tierTarget): array
    {
        [$provider, $model] = $this->parseTarget($tierTarget);
        $attempts = [];

        if ($provider === self::PROVIDER_SUMOPOD) {
            if ($this->sumopodReady()) {
                $attempts[] = ['provider' => self::PROVIDER_SUMOPOD, 'model' => $model];

                // Within-provider fallback: the globally configured
                // SumoPod model, when it differs from the tier's pick.
                $global = (string) Setting::get('ai_sumopod_model');
                if (filled($global) && $global !== $model) {
                    $attempts[] = ['provider' => self::PROVIDER_SUMOPOD, 'model' => $global];
                }
            }
        } else {
            if ($this->openrouterEnabled()) {
                $attempts[] = ['provider' => self::PROVIDER_OPENROUTER, 'model' => $model];
            }

            // Cross-provider fallback: SumoPod's globally configured
            // model steps in when OpenRouter is off or fails.
            if ($this->sumopodUsable()) {
                $attempts[] = [
                    'provider' => self::PROVIDER_SUMOPOD,
                    'model' => (string) Setting::get('ai_sumopod_model'),
                ];
            }
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

    /** SumoPod can serve a tier-picked model: enabled + key present. */
    public function sumopodReady(): bool
    {
        return $this->sumopodEnabled()
            && filled(Setting::get('ai_sumopod_api_key'));
    }

    /** SumoPod can also step in cross-provider: a global model is set. */
    public function sumopodUsable(): bool
    {
        return $this->sumopodReady()
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
