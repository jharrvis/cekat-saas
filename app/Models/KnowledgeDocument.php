<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id',
        'name',
        'type',
        'file_path',
        'url',
        'content',
        'chunks',
        'status',
    ];

    protected $casts = [
        'chunks' => 'array',
    ];

    /**
     * Get the knowledge base that owns the document.
     */
    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}
