<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AdminSettingsChanged
{
    use Dispatchable;

    public function __construct(
        public readonly string $group,
        public readonly ?int $userId,
        public readonly array $keys = [],
    ) {}
}
