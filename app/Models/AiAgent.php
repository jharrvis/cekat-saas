<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AiAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'avatar_url',
        'ai_model',
        'ai_temperature',
        'system_prompt',
        'personality',
        'max_tokens',
        'language',
        'fallback_message',
        'greeting_message',
        'messages_used',
        'conversations_count',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'ai_temperature' => 'float',
        'max_tokens' => 'integer',
        'messages_used' => 'integer',
        'conversations_count' => 'integer',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agent) {
            if (empty($agent->slug)) {
                $agent->slug = static::generateUniqueSlug($agent->name);
            }
        });
    }

    /**
     * Generate a unique slug for the agent.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the user that owns this agent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get widgets using this agent.
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Get the knowledge base for this agent.
     */
    public function knowledgeBase(): HasOne
    {
        return $this->hasOne(KnowledgeBase::class);
    }

    /**
     * Get chat sessions handled by this agent.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'current_agent_id');
    }

    /**
     * Get messages sent by this agent.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the AI model to use for this agent.
     */
    public function getAiModelAttribute($value): string
    {
        return $value ?? 'google/gemini-2.0-flash-001';
    }

    /**
     * Get default system prompt if not set.
     */
    public function getSystemPromptWithDefaults(): string
    {
        if (!empty($this->system_prompt)) {
            return $this->system_prompt;
        }

        $prompts = [
            'professional' => 'Anda adalah asisten profesional yang membantu pelanggan dengan sopan dan informatif.',
            'friendly' => 'Anda adalah asisten yang ramah dan helpful. Jawab pertanyaan dengan hangat dan bersahabat.',
            'casual' => 'Kamu adalah asisten yang santai dan gaul. Jawab dengan bahasa casual tapi tetap informatif.',
            'formal' => 'Anda adalah asisten formal yang menjawab pertanyaan dengan bahasa baku dan profesional.',
        ];

        return $prompts[$this->personality] ?? $prompts['friendly'];
    }

    /**
     * Get fallback message with default.
     */
    public function getFallbackMessageWithDefault(): string
    {
        return $this->fallback_message ?? 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.';
    }

    /**
     * Get greeting message with default.
     */
    public function getGreetingMessageWithDefault(): string
    {
        return $this->greeting_message ?? 'Halo! 👋 Ada yang bisa saya bantu?';
    }

    /**
     * Increment messages used counter.
     */
    public function incrementMessagesUsed(int $count = 1): void
    {
        $this->increment('messages_used', $count);
    }

    /**
     * Increment conversations counter.
     */
    public function incrementConversations(): void
    {
        $this->increment('conversations_count');
    }

    /**
     * Scope for active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get count of widgets using this agent.
     */
    public function getWidgetCountAttribute(): int
    {
        return $this->widgets()->count();
    }

    /**
     * Check if agent has knowledge base with content.
     */
    public function hasKnowledgeContent(): bool
    {
        $kb = $this->knowledgeBase;
        if (!$kb)
            return false;

        return $kb->faqs()->count() > 0 || $kb->documents()->count() > 0;
    }
}
