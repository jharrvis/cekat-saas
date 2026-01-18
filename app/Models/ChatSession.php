<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_id',
        'current_agent_id',
        'session_id',
        'visitor_uuid',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'source_url',
        'user_agent',
        'ip_address',
        'started_at',
        'ended_at',
        'is_converted',
        'status',
        'summary',
        'summary_generated_at',
        'device_type',
        'location_data',
        'referer_url',
        'is_lead',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'summary_generated_at' => 'datetime',
        'is_converted' => 'boolean',
        'is_lead' => 'boolean',
        'location_data' => 'array',
    ];

    /**
     * Get the widget that owns the chat session.
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get the current AI agent handling this session.
     */
    public function currentAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'current_agent_id');
    }

    /**
     * Get the messages for the chat session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    /**
     * Get effective AI agent (from session or widget fallback).
     */
    public function getEffectiveAgent(): ?AiAgent
    {
        if ($this->currentAgent) {
            return $this->currentAgent;
        }
        return $this->widget?->aiAgent;
    }
}
