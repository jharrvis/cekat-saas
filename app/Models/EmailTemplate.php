<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'email_templates';

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body',
        'category',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Token placeholders available in template/campaign bodies.
     *
     * @return array<string, string>
     */
    public static function tokens(): array
    {
        return [
            '{{name}}' => 'Nama penerima',
            '{{email}}' => 'Email penerima',
            '{{app_name}}' => 'Nama aplikasi',
            '{{login_url}}' => 'URL login',
            '{{plan}}' => 'Plan penerima',
            '{{date}}' => 'Tanggal hari ini',
        ];
    }

    /**
     * Replace {{tokens}} with sample data (preview) or real user data.
     *
     * @return array{0: string, 1: string} [subject, body]
     */
    public static function renderTokens(string $subject, string $body, array $values): array
    {
        $map = [
            '{{name}}' => (string) ($values['name'] ?? ''),
            '{{email}}' => (string) ($values['email'] ?? ''),
            '{{app_name}}' => (string) ($values['app_name'] ?? config('app.name')),
            '{{login_url}}' => (string) ($values['login_url'] ?? route('login')),
            '{{plan}}' => (string) ($values['plan'] ?? 'Free'),
            '{{date}}' => (string) ($values['date'] ?? now()->format('d/m/Y')),
        ];

        return [strtr($subject, $map), strtr($body, $map)];
    }
}
