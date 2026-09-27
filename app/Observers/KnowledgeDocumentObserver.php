<?php

namespace App\Observers;

use App\Events\DocumentStatusChanged;
use App\Models\KnowledgeDocument;

class KnowledgeDocumentObserver
{
    public function updated(KnowledgeDocument $document): void
    {
        if ($document->wasChanged('status')) {
            DocumentStatusChanged::dispatch(
                $document->id,
                $document->knowledge_base_id,
                $document->getOriginal('status'),
                $document->status,
            );
        }
    }
}
