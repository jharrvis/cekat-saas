<?php

namespace App\Providers;

use App\Listeners\LogSystemEvent;
use App\Models\KnowledgeDocument;
use App\Observers\KnowledgeDocumentObserver;
use Illuminate\Support\Facades\Event;
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
        KnowledgeDocument::observe(KnowledgeDocumentObserver::class);
    }
}
