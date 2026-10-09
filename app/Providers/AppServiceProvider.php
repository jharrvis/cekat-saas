<?php

namespace App\Providers;

use App\Listeners\AlertWidgetAbuseSpike;
use App\Listeners\LogSystemEvent;
use App\Models\KnowledgeDocument;
use App\Observers\KnowledgeDocumentObserver;
use App\Support\HttpClientIp;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(LogSystemEvent::class);
        Event::subscribe(AlertWidgetAbuseSpike::class);
        KnowledgeDocument::observe(KnowledgeDocumentObserver::class);

        // Public widget chat endpoint: blunt per-IP+widget and per-IP caps so
        // scripted prompt scraping / quota farming cannot run unthrottled.
        // Real conversations run at a few messages per minute, so 30/min per
        // widget and 120/min per IP are far above human traffic.
        RateLimiter::for('chat', function (Request $request) {
            $ip = HttpClientIp::get($request);
            $widget = (string) $request->input('widgetId', 'default');

            return [
                Limit::perMinute(30)->by($ip.'|'.$widget),
                Limit::perMinute(120)->by($ip),
            ];
        });

        // Public read API (/api/v1): keyed on the authenticated API key so
        // one noisy integration cannot exhaust another's quota. Unauthenticated
        // attempts (rejected by ApiKeyAuth before this runs) fall back to IP.
        RateLimiter::for('api-key', function (Request $request) {
            $apiKey = $request->attributes->get('apiKey');
            $id = $apiKey?->id ?? 'ip:' . HttpClientIp::get($request);

            return Limit::perMinute(120)->by('api-key:' . $id);
        });
    }
}
