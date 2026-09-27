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

        if (in_array($originDomain, $allowedList, true)) {
            return true;
        }

        if (Str::contains($origin, 'localhost') || Str::contains($origin, '127.0.0.1')) {
            return true;
        }

        return false;
    }
}
