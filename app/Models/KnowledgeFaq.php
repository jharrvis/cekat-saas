<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id',
        'question',
        'answer',
        'sort_order',
    ];

    /**
     * Get the knowledge base that owns the FAQ.
     */
    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}
