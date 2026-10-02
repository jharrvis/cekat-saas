<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    public const TYPES = ['newsletter', 'announcement'];
    public const STATUSES = ['draft', 'sending', 'sent', 'stopped', 'failed'];

    public const TYPE_LABELS = [
        'newsletter' => 'Newsletter',
        'announcement' => 'Pengumuman',
    ];

    protected $table = 'email_campaigns';

    protected $fillable = [
        'type',
        'name',
        'subject',
        'body',
        'segment',
        'recipients',
        'status',
        'cursor',
        'total_recipients',
        'sent_count',
        'failed_count',
        'started_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'segment' => 'array',
        'recipients' => 'array',
        'cursor' => 'integer',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'started_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }

    public function progress(): int
    {
        return $this->total_recipients > 0
            ? (int) min(100, round($this->cursor / $this->total_recipients * 100))
            : 0;
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }
}
