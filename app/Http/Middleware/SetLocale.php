<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * T-12: resolve the active locale for every request.
 *
 * Priority: signed-in user's `users.locale` → session `locale` (guests who
 * picked a language) → `Accept-Language` header (API consumers) → the app
 * default (Indonesian). Only locales that actually ship a lang/ folder are
 * honored, so adding a language later needs no code change.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = self::availableLocales();
        $locale = null;

        $user = $request->user();
        if ($user && ! empty($user->locale) && in_array($user->locale, $available, true)) {
            $locale = $user->locale;
        }

        if ($locale === null && $request->hasSession()) {
            $fromSession = $request->session()->get('locale');
            if (is_string($fromSession) && in_array($fromSession, $available, true)) {
                $locale = $fromSession;
            }
        }

        // Accept-Language steers API consumers only (T-12 spec): web guests
        // without an explicit choice always land on the default locale.
        // It also only counts when the client actually sent the header —
        // getPreferredLanguage() otherwise falls back to the first entry of
        // the available list, which would silently flip the default.
        if ($locale === null && $request->is('api/*') && $request->headers->has('Accept-Language')) {
            $preferred = $request->getPreferredLanguage($available);
            if (is_string($preferred) && $preferred !== '') {
                $locale = $preferred;
            }
        }

        App::setLocale($locale ?: config('app.locale', 'id'));

        return $next($request);
    }

    /**
     * @return list<string> locale codes that have a lang/ directory
     */
    public static function availableLocales(): array
    {
        $dirs = glob(base_path('lang/*'), GLOB_ONLYDIR) ?: [];
        $codes = array_map(fn ($path) => basename($path), $dirs);
        sort($codes);

        return $codes !== [] ? $codes : ['id'];
    }
}
