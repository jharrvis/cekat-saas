<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers on every response.
 *
 * CSP is intentionally limited to directives that cannot break existing
 * pages (no script-src: the app, Livewire and the landing page rely on
 * inline scripts). script-src should be introduced as a separate,
 * page-by-page task.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
