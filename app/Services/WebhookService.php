<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Send a webhook request with HMAC signature.
     *
     * @param string $url The target URL
     * @param array $payload The data to send
     * @param string $secret The secret key for signing
     * @return array Response details
     */
    public function send(string $url, array $payload, string $secret)
    {
        try {
            $timestamp = time();
            $signature = $this->generateSignature($payload, $secret, $timestamp);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Cekat-Signature' => $signature,
                'X-Cekat-Timestamp' => $timestamp,
                'User-Agent' => 'Cekat-SaaS/1.0',
            ])->timeout(10)->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Webhook Send Error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate HMAC SHA256 signature.
     * 
     * Signature = HMAC_SHA256(timestamp + "." + json_payload, secret)
     */
    private function generateSignature(array $payload, string $secret, int $timestamp): string
    {
        $jsonPayload = json_encode($payload);
        $dataToSign = $timestamp . '.' . $jsonPayload;

        return hash_hmac('sha256', $dataToSign, $secret);
    }
}
