<?php

namespace App\Models;

use App\Support\CipherText;
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
     * Transcript content is encrypted at rest (new rows); legacy plaintext
     * rows are returned as-is and age out via chat:purge.
     */
    public function getContentAttribute($value): ?string
    {
        return CipherText::decrypt($value);
    }

    public function setContentAttribute(?string $value): void
    {
        $this->attributes['content'] = CipherText::encrypt($value);
    }

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
