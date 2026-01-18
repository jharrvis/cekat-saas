<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'ai_agent_id',
        'role',
        'content',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    /**
     * Get the chat session that owns the message.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    /**
     * Alias for session for backward compatibility.
     */
    public function chatSession(): BelongsTo
    {
        return $this->session();
    }

    /**
     * Get the AI agent that sent this message.
     */
    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }
}
