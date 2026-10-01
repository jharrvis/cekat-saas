<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ChatRequestProcessed
{
    use Dispatchable;

    public function __construct(
        public readonly string $widgetSlug,
        public readonly ?int $userId,
        public readonly string $sessionId,
        public readonly string $model,
        public readonly int $tokensUsed,
    ) {}
}
