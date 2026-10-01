<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class QuotaExceeded
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $widgetSlug,
        public readonly int $used,
        public readonly int $limit,
    ) {}
}
