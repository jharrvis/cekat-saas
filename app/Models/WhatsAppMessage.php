<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'whatsapp_device_id',
        'widget_id',
        'sender_phone',
        'sender_name',
        'direction',
        'message',
        'message_type',
        'media_url',
        'status',
        'fonnte_message_id',
        'error_message',
        'is_ai_response',
        'ai_model_used',
        'tokens_used',
    ];

    protected $casts = [
        'is_ai_response' => 'boolean',
    ];

    /**
     * Get the WhatsApp device for this message.
     */
    public function device()
    {
        return $this->belongsTo(WhatsAppDevice::class, 'whatsapp_device_id');
    }

    /**
     * Get the widget linked to this message.
     */
    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Check if message is inbound (received from customer).
     */
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    /**
     * Check if message is outbound (sent to customer).
     */
    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    /**
     * Scope: Inbound messages only.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope: Outbound messages only.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope: AI responses only.
     */
    public function scopeAiResponses($query)
    {
        return $query->where('is_ai_response', true);
    }
}
