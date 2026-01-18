<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ai_agent_id',
        'name',
        'display_name',
        'description',
        'slug',
        'settings',
        'is_active',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the widget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI Agent for this widget.
     */
    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    /**
     * Get the knowledge base for the widget.
     * @deprecated Use aiAgent->knowledgeBase instead
     */
    public function knowledgeBase(): HasOne
    {
        return $this->hasOne(KnowledgeBase::class);
    }

    /**
     * Get effective knowledge base (from agent or direct).
     */
    public function getEffectiveKnowledgeBase(): ?KnowledgeBase
    {
        // First try from AI Agent
        if ($this->aiAgent && $this->aiAgent->knowledgeBase) {
            return $this->aiAgent->knowledgeBase;
        }
        // Fallback to direct relationship (backward compat)
        return $this->knowledgeBase;
    }

    /**
     * Get the chat sessions for the widget.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get all messages across all sessions for this widget.
     */
    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(ChatMessage::class, ChatSession::class, 'widget_id', 'chat_session_id');
    }

    /**
     * Get the WhatsApp device for this widget.
     */
    public function whatsappDevice(): HasOne
    {
        return $this->hasOne(WhatsAppDevice::class);
    }

    /**
     * Check if widget has an active AI agent.
     */
    public function hasActiveAgent(): bool
    {
        return $this->aiAgent && $this->aiAgent->is_active;
    }

    /**
     * Get AI model to use (from agent or settings fallback).
     */
    public function getAiModel(): string
    {
        if ($this->aiAgent) {
            return $this->aiAgent->ai_model;
        }
        return $this->settings['ai_model'] ?? 'google/gemini-2.0-flash-001';
    }
}
