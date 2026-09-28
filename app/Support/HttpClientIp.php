<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the real client IP behind the Cloudflare edge.
 *
 * In production every TCP peer is a Cloudflare proxy, so request()->ip()
 * would collapse all visitors into one rate-limit bucket. Cloudflare sets
 * CF-Connecting-IP on every proxied request; the value is validated as an
 * IP so a spoofed header from a direct-to-origin request is ignored.
 */
class HttpClientIp
{
    public static function get(?Request $request = null): string
    {
        $request ??= request();

        $cf = trim((string) $request->header('CF-Connecting-IP'));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) !== false) {
            return $cf;
        }

        return $request->ip() ?? '0.0.0.0';
    }
}
