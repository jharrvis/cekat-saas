<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LeadCaptured
{
    use Dispatchable;

    /**
     * @param array $fieldsPresent Names of lead fields present (no PII values logged).
     */
    public function __construct(
        public readonly string $widgetSlug,
        public readonly array $fieldsPresent,
    ) {}
}
