<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'max_widgets',
        'max_messages_per_month',
        'max_documents',
        'max_file_size_mb',
        'max_faqs',
        'chat_history_days',
        'can_export_leads',
        'can_use_whatsapp',
        'allowed_models',
        'features',
        'ai_tier',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'allowed_models' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'can_export_leads' => 'boolean',
        'can_use_whatsapp' => 'boolean',
        'max_widgets' => 'integer',
        'max_messages_per_month' => 'integer',
        'max_documents' => 'integer',
        'max_file_size_mb' => 'integer',
        'max_faqs' => 'integer',
        'chat_history_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get users with this plan
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if a model is allowed for this plan
     */
    public function allowsModel(string $model): bool
    {
        return in_array($model, $this->allowed_models ?? []);
    }

    /**
     * Check if a feature is enabled for this plan
     */
    public function hasFeature(string $feature): bool
    {
        return ($this->features[$feature] ?? false) === true;
    }
}
