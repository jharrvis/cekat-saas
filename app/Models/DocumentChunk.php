<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_document_id',
        'chunk_index',
        'content',
        'token_count',
        'embedding',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
    ];

    /**
     * Get the document that owns the chunk.
     */
    public function document()
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    /**
     * Estimate token count (rough approximation: ~4 chars per token)
     */
    public function estimateTokens(): int
    {
        return (int) ceil(strlen($this->content) / 4);
    }
}
