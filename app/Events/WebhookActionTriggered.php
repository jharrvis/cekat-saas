<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WebhookActionTriggered
{
    use Dispatchable;

    public function __construct(
        public readonly string $widgetSlug,
        public readonly string $action,
    ) {}
}
