<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmModel extends Model
{
    protected $fillable = [
        'model_id',
        'name',
        'provider',
        'description',
        'input_price',
        'output_price',
        'context_length',
        'allowed_tiers',
        'is_active',
        'popularity',
    ];

    protected $casts = [
        'allowed_tiers' => 'array',
        'is_active' => 'boolean',
        'input_price' => 'decimal:6',
        'output_price' => 'decimal:6',
    ];

    /**
     * Check if model is available for given tier
     */
    public function isAvailableForTier(string $tier): bool
    {
        if (empty($this->allowed_tiers)) {
            return true; // Available for all if not specified
        }

        return in_array($tier, $this->allowed_tiers);
    }

    /**
     * Get models available for a specific tier
     */
    public static function forTier(string $tier)
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($tier) {
                $query->whereJsonContains('allowed_tiers', $tier)
                    ->orWhereNull('allowed_tiers');
            })
            ->orderBy('popularity', 'desc')
            ->get();
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->input_price == 0 && $this->output_price == 0) {
            return 'Free';
        }

        return '$' . number_format($this->input_price, 4) . ' / 1M tokens';
    }
}
