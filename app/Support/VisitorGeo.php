<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Best-effort visitor context for chat sessions: device class, a short
 * human browser label, and coarse location (country/city).
 *
 * Geo lookup runs after the chat response (see ChatOrchestrator) and is
 * allowed to fail silently - persistence must never depend on it.
 */
class VisitorGeo
{
    public const GEO_URL = 'https://ipwho.is/';

    /**
     * Only public addresses are worth a geo lookup. Loopback/private/
     * reserved ranges (local dev, tests) return false immediately so
     * tests never hit the network.
     */
    public static function isPublicIp(?string $ip): bool
    {
        if (! $ip || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Resolve coarse location for an IP via ipwho.is (no key, https).
     * Country may also come from Cloudflare's CF-IPCountry header when
     * the provider call fails or the IP is not public.
     *
     * @return array<string,string>|null e.g. ['country_code'=>'ID','country'=>'Indonesia','city'=>'Jakarta']
     */
    public static function resolve(?string $ip): ?array
    {
        $data = [];

        $cfCountry = request()?->header('CF-IPCountry');
        if (is_string($cfCountry) && $cfCountry !== '' && $cfCountry !== 'XX') {
            $data['country_code'] = $cfCountry;
        }

        if (self::isPublicIp($ip)) {
            try {
                $response = Http::connectTimeout(1)->timeout(2)->get(self::GEO_URL . $ip);

                if ($response->successful() && ($response->json('success') ?? false)) {
                    $data = array_merge($data, array_filter([
                        'country_code' => $response->json('country_code'),
                        'country' => $response->json('country'),
                        'region' => $response->json('region'),
                        'city' => $response->json('city'),
                        'isp' => $response->json('connection.isp'),
                    ], fn ($value) => $value !== null && $value !== ''));
                }
            } catch (\Throwable $e) {
                // Best effort only - a geo timeout must never break chat persistence.
            }
        }

        return $data ?: null;
    }

    /**
     * Coarse device class stored in chat_sessions.device_type.
     */
    public static function deviceType(?string $ua): string
    {
        if ($ua === null || $ua === '') {
            return 'desktop';
        }

        if (preg_match('/iPad|Tablet|PlayBook|Silk\//i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobi|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Short human label for chat detail UI, e.g. "Chrome 153 · Windows".
     * The full user agent is kept in the DB but never rendered.
     */
    public static function describeAgent(?string $ua): ?string
    {
        if (! $ua) {
            return null;
        }

        $browser = null;

        if (preg_match('/Edg(?:e|A|iOS)?\/(\d+)/', $ua, $m)) {
            $browser = 'Edge ' . $m[1];
        } elseif (preg_match('/OPR\/(\d+)/', $ua, $m)) {
            $browser = 'Opera ' . $m[1];
        } elseif (preg_match('/Firefox\/(\d+)/', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/Chrome\/(\d+)/', $ua, $m)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/Version\/[\d.]+.*Safari|Safari\//', $ua)) {
            $browser = 'Safari';
        }

        $os = null;

        if (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/', $ua)) {
            $os = 'iOS';
        } elseif (stripos($ua, 'Mac OS X') !== false || stripos($ua, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        $label = trim(($browser ?? 'Browser') . ($os ? ' · ' . $os : ''));

        return $label ?: null;
    }
}
