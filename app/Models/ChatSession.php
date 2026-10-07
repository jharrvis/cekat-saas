<?php

namespace App\Models;

use App\Support\CipherText;
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
        'is_preview',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'summary_generated_at' => 'datetime',
        'is_converted' => 'boolean',
        'is_lead' => 'boolean',
        'is_preview' => 'boolean',
        'location_data' => 'array',
    ];

    /**
     * T-07: only real visitor sessions (exclude owner test/preview sessions)
     * for customer-facing history, stats, and lead surfaces.
     */
    public function scopeReal($query)
    {
        return $query->where('is_preview', false);
    }

    /**
     * Visitor identity + AI summary are encrypted at rest (new rows);
     * legacy plaintext rows are returned as-is and age out via chat:purge.
     *
     * NOTE: LIKE search on these columns only matches legacy rows.
     */
    public function getVisitorNameAttribute($value): ?string
    {
        return CipherText::decrypt($value);
    }

    public function setVisitorNameAttribute(?string $value): void
    {
        $this->attributes['visitor_name'] = CipherText::encrypt($value);
    }

    public function getVisitorEmailAttribute($value): ?string
    {
        return CipherText::decrypt($value);
    }

    public function setVisitorEmailAttribute(?string $value): void
    {
        $this->attributes['visitor_email'] = CipherText::encrypt($value);
    }

    public function getVisitorPhoneAttribute($value): ?string
    {
        return CipherText::decrypt($value);
    }

    public function setVisitorPhoneAttribute(?string $value): void
    {
        $this->attributes['visitor_phone'] = CipherText::encrypt($value);
    }

    public function getSummaryAttribute($value): ?string
    {
        return CipherText::decrypt($value);
    }

    public function setSummaryAttribute(?string $value): void
    {
        $this->attributes['summary'] = CipherText::encrypt($value);
    }

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
