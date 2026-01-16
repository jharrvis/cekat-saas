<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
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
    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
