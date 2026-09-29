<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LeadCaptured
{
    use Dispatchable;

    /**
     * @param array $fieldsPresent Names of lead fields present (no PII values logged).
     * @param string|null $sessionId Widget session id (used to persist the lead).
     * @param array $lead Raw lead values for the owner notification email only
     *                    (never logged - LogSystemEvent only reads $fieldsPresent).
     */
    public function __construct(
        public readonly string $widgetSlug,
        public readonly array $fieldsPresent,
        public readonly ?string $sessionId = null,
        public readonly array $lead = [],
    ) {}
}
