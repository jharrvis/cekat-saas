<?php

namespace App\Http\Middleware;

use App\Models\Widget;
use App\Services\Chat\DomainAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CORS for the public widget API (replaces the framework HandleCors
 * wildcard on api/*, which let any website read chat responses).
 *
 * The Access-Control-Allow-Origin header is only echoed for origins the
 * target widget actually allows (DomainAccessService), so a hostile site
 * can never read responses of widgets locked to other domains. Preflights
 * echo the origin unconditionally - they grant nothing by themselves,
 * because the real (GET/POST) response still has to pass the same check.
 */
class WidgetApiCors
{
    public function __construct(protected DomainAccessService $domains) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->preflight($request);
        }

        $response = $next($request);

        $origin = $this->origin($request);
        if ($origin !== null && $this->domains->isAllowed($this->allowlist($request), $origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $this->originValue($origin));
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    protected function preflight(Request $request): Response
    {
        $origin = $this->origin($request);

        $response = response()->noContent();

        if ($origin !== null && $this->originValue($origin) !== null) {
            $response
                ->header('Access-Control-Allow-Origin', $this->originValue($origin))
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Vary', 'Origin');
        }

        return $response;
    }

    protected function origin(Request $request): ?string
    {
        $origin = $request->header('Origin') ?: $request->header('Referer');

        return ($origin !== null && $origin !== '') ? $origin : null;
    }

    /**
     * ACAO must be scheme://host[:port] - a Referer carries a path, so the
     * origin is rebuilt from its parts before it is echoed.
     */
    protected function originValue(string $origin): ?string
    {
        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);

        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;

        return $scheme.'://'.$host.($port && (int) $port !== $defaultPort ? ':'.$port : '');
    }

    protected function allowlist(Request $request): ?string
    {
        $slug = $request->route()?->parameter('slug') ?? $request->input('widgetId');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $widget = Widget::query()->where('slug', $slug)->first(['settings']);
        $settings = $widget?->settings;

        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        return is_array($settings) ? ($settings['allowed_domains'] ?? null) : null;
    }
}
