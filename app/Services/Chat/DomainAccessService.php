<?php

namespace App\Services\Chat;

use Illuminate\Support\Str;

/**
 * Validates whether an incoming widget/chat request origin is allowed
 * by the widget's `allowed_domains` setting.
 *
 * Extracted from Api\ChatController and the /api/widget/{slug}/config
 * route closure (previously duplicated in both places).
 */
class DomainAccessService
{
    /**
     * @param string|null $allowedCsv Comma-separated domains from widget settings
     * @param string|null $origin     Origin or Referer header value
     */
    public function isAllowed(?string $allowedCsv, ?string $origin): bool
    {
        if (empty(trim($allowedCsv ?? ''))) {
            return true;
        }

        // Preserve legacy behavior: only enforced when the browser sends Origin/Referer.
        if (empty($origin)) {
            return true;
        }

        $originDomain = parse_url($origin, PHP_URL_HOST);
        $allowedList = array_map('trim', explode(',', $allowedCsv));

        // The application's own host may always use any widget (landing page,
        // dashboard test chat, local development) regardless of the allowlist.
        if (is_string($originDomain) && $originDomain === $this->ownHost()) {
            return true;
        }

        if ($this->hostMatchesList($originDomain, $allowedList)) {
            return true;
        }

        if (Str::contains($origin, 'localhost') || Str::contains($origin, '127.0.0.1')) {
            return true;
        }

        return false;
    }

    /**
     * Exact + subdomain + www-tolerant match, mirroring the client-side
     * gate in public/widget/widget.js isDomainAllowed(). The UI promises
     * "mysite.com also allows www./blog.mysite.com" - an exact-only server
     * check used to 403 exactly those subdomains.
     */
    protected function hostMatchesList(?string $host, array $allowedList): bool
    {
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = Str::lower($host);

        foreach ($allowedList as $entry) {
            $entry = Str::lower($entry);

            if ($entry === '') {
                continue;
            }

            if ($host === $entry) {
                return true;
            }

            // entry "mysite.com" matches "www.mysite.com", "blog.mysite.com"
            if (Str::endsWith($host, '.' . $entry)) {
                return true;
            }

            // www tolerance in the entry itself
            if (Str::startsWith($entry, 'www.')) {
                $withoutWww = Str::after($entry, 'www.');

                if ($host === $withoutWww || Str::endsWith($host, '.' . $withoutWww)) {
                    return true;
                }
            } elseif ($host === 'www.' . $entry) {
                return true;
            }
        }

        return false;
    }

    protected function ownHost(): ?string
    {
        $url = (string) config('app.url');

        return $url !== '' ? parse_url($url, PHP_URL_HOST) : null;
    }
}
