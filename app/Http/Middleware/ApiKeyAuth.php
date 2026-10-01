<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the public read API (/api/v1) with a bearer API key
 * (Authorization: Bearer ck_live_... or X-API-Key: ck_live_...).
 *
 * On success the owning user is bound to the request (user resolver)
 * and the ApiKey model is stored in the request attributes so the
 * rate limiter can key on it. Failures always return JSON 401/403 -
 * this middleware only ever runs for server-to-server calls.
 */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->bearerToken() ?: $request->header('X-API-Key');

        if (! is_string($secret) || $secret === '') {
            return $this->deny($request, 401, 'unauthenticated', 'API key wajib. Kirim header Authorization: Bearer <api_key>.');
        }

        $key = $this->findKey($secret);

        if (! $key || ! $key->isUsable()) {
            return $this->deny($request, 401, 'unauthenticated', 'API key tidak valid, sudah dicabut, atau kedaluwarsa.');
        }

        $owner = $key->user;

        if (! $owner) {
            return $this->deny($request, 401, 'unauthenticated', 'API key tidak valid, sudah dicabut, atau kedaluwarsa.');
        }

        if (! $owner->canUseApi()) {
            return $this->deny($request, 403, 'feature_locked', 'API access tidak tersedia pada paket Anda. Upgrade paket untuk memakai API key.', [
                'upgrade' => route('billing'),
            ]);
        }

        if (($owner->status ?? 'active') !== 'active') {
            return $this->deny($request, 403, 'account_suspended', 'Akun tidak aktif.');
        }

        $key->markUsed();

        $request->setUserResolver(fn () => $owner);
        $request->attributes->set('apiKey', $key);

        return $next($request);
    }

    private function findKey(string $secret): ?ApiKey
    {
        // Lookup narrows on the non-secret prefix; the constant-time
        // comparison happens on the full sha256 hash.
        $candidates = ApiKey::query()
            ->where('key_prefix', substr($secret, 0, 12))
            ->get();

        $hash = hash('sha256', $secret);

        foreach ($candidates as $candidate) {
            if (hash_equals($candidate->key_hash, $hash)) {
                return $candidate;
            }
        }

        return null;
    }

    private function deny(Request $request, int $status, string $code, string $message, array $extra = []): Response
    {
        return response()->json(array_merge([
            'error' => $code,
            'error_code' => $code,
            'message' => $message,
        ], $extra), $status);
    }
}
