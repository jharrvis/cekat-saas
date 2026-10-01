<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterClient
{
    /**
     * Kirim chat completion ke OpenRouter dengan fallback model.
     *
     * Urutan: model utama (payload['model']) -> config('services.openrouter.fallback_model').
     * Fallback dipakai jika primary error (404 model mati, 429 rate limit, 402, 5xx)
     * atau respons tidak berisi konten.
     *
     * @return array Respons JSON dari OpenRouter.
     */
    public static function chatCompletion(array $payload, int $timeout = 60, string $title = 'Cekat SaaS'): array
    {
        $primary = $payload['model'] ?? config('services.openrouter.default_model');
        $fallback = config('services.openrouter.fallback_model');

        $models = array_values(array_unique(array_filter([$primary, $fallback])));

        $last = [];
        foreach ($models as $index => $model) {
            $payload['model'] = $model;

            $last = self::post($payload, $timeout, $title);

            if (self::isUsable($last)) {
                if ($index > 0) {
                    Log::warning('OpenRouter fallback aktif', [
                        'primary_model' => $primary,
                        'model_digunakan' => $model,
                        'error_primary' => $last['_primary_error'] ?? null,
                    ]);
                }
                unset($last['_primary_error']);

                return $last;
            }

            $last['_primary_error'] = self::errorSummary($last);
        }

        Log::error('OpenRouter semua model gagal', [
            'models' => $models,
            'error' => self::errorSummary($last),
        ]);

        unset($last['_primary_error']);

        return $last;
    }

    private static function post(array $payload, int $timeout, string $title): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
                'HTTP-Referer' => config('app.url'),
                'X-Title' => $title,
            ])->timeout($timeout)->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 'connection_failed',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    private static function isUsable(array $data): bool
    {
        if (!empty($data['error'])) {
            return false;
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        return is_string($content) && trim($content) !== '';
    }

    private static function errorSummary(array $data): ?string
    {
        if (empty($data['error'])) {
            return 'empty_content';
        }

        $error = $data['error'];

        if (is_string($error)) {
            return $error;
        }

        $code = $error['code'] ?? 'unknown';

        return $code . ': ' . ($error['message'] ?? 'no message');
    }
}
