<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentId,
        public readonly int $knowledgeBaseId,
        public readonly ?string $from,
        public readonly string $to,
    ) {}
}
