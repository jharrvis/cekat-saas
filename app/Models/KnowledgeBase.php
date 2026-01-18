<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_id',
        'ai_agent_id',
        'company_name',
        'company_description',
        'persona_name',
        'persona_tone',
        'custom_instructions',
        'customer_greeting',
    ];

    /**
     * Get the AI Agent that owns this knowledge base.
     */
    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    /**
     * Get the widget that owns the knowledge base.
     * @deprecated Use aiAgent instead
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get the FAQs for the knowledge base.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(KnowledgeFaq::class);
    }

    /**
     * Get the documents for the knowledge base.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    /**
     * Get the user that owns this knowledge base.
     */
    public function user()
    {
        // Try via AI Agent first, then widget for backward compat
        if ($this->aiAgent) {
            return $this->aiAgent->user;
        }
        return $this->widget?->user;
    }

    /**
     * Check if knowledge base has any content.
     */
    public function hasContent(): bool
    {
        return $this->faqs()->count() > 0 || $this->documents()->count() > 0;
    }
}

