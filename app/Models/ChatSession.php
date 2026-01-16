<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_id',
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
    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get the messages for the chat session.
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }
}
