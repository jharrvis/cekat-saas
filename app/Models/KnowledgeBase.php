<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_id',
        'company_name',
        'company_description',
        'persona_name',
        'persona_tone',
        'custom_instructions',
    ];

    /**
     * Get the widget that owns the knowledge base.
     */
    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get the FAQs for the knowledge base.
     */
    public function faqs()
    {
        return $this->hasMany(KnowledgeFaq::class);
    }

    /**
     * Get the documents for the knowledge base.
     */
    public function documents()
    {
        return $this->hasMany(KnowledgeDocument::class);
    }
}
