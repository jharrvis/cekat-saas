<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DomainBlocked
{
    use Dispatchable;

    public function __construct(
        public readonly string $widgetSlug,
        public readonly ?string $origin,
    ) {}
}
