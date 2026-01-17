<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the knowledge base for the widget.
     */
    public function knowledgeBase()
    {
        return $this->hasOne(KnowledgeBase::class);
    }

    /**
     * Get the chat sessions for the widget.
     */
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get all messages across all sessions for this widget.
     */
    public function messages()
    {
        return $this->hasManyThrough(ChatMessage::class, ChatSession::class, 'widget_id', 'session_id');
    }

    /**
     * Get the WhatsApp device for this widget.
     */
    public function whatsappDevice()
    {
        return $this->hasOne(WhatsAppDevice::class);
    }
}
