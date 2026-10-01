<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Personal API key for the public read API (/api/v1).
 * Only the sha256 hash is stored; the plaintext secret is returned
 * once at creation and never persisted.
 */
class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mint a new key. Returns [$model, $plaintextSecret] - the caller must
     * show $plaintextSecret to the user exactly once.
     *
     * @return array{0: self, 1: string}
     */
    public static function generate(User $user, string $name): array
    {
        $secret = 'ck_live_' . Str::random(44);

        $key = self::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_prefix' => substr($secret, 0, 12),
            'key_hash' => hash('sha256', $secret),
        ]);

        return [$key, $secret];
    }

    public function isUsable(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function markUsed(): void
    {
        // Avoid a write on every request: persist at most once a minute.
        if ($this->last_used_at === null || $this->last_used_at->lte(now()->subMinute())) {
            $this->forceFill(['last_used_at' => now()])->saveQuietly();
        }
    }
}
