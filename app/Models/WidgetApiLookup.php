<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Definisi API Lookup milik sebuah channel (widget).
 *
 * Chatbot memanggil lookup ini saat percakapan via blok JSON
 * {"action": "api_lookup", "lookup": "<key>", "params": {...}},
 * dieksekusi oleh ApiLookupService.
 */
class WidgetApiLookup extends Model
{
    public const AUTH_TYPES = ['none', 'bearer', 'api_key_header', 'api_key_query'];

    // Fase 1: hanya GET. POST menyusul setelah ada persetujuan eksplisit
    // karena dapat mengubah data di sistem tujuan.
    public const METHODS = ['GET'];

    protected $fillable = [
        'widget_id',
        'key',
        'name',
        'description',
        'method',
        'url_template',
        'params',
        'headers',
        'auth_type',
        'auth_secret',
        'auth_config',
        'timeout_seconds',
        'response_mapping',
        'empty_path',
        'reply_template',
        'empty_message',
        'error_message',
        'cache_ttl_seconds',
        'is_active',
    ];

    protected $casts = [
        'params' => 'array',
        'headers' => 'encrypted:array',
        'auth_config' => 'array',
        'auth_secret' => 'encrypted',
        'response_mapping' => 'array',
        'timeout_seconds' => 'integer',
        'cache_ttl_seconds' => 'integer',
        'is_active' => 'boolean',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Data ringkas untuk katalog prompt AI (tanpa secret).
     */
    public function toPromptArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'params' => collect($this->params ?? [])->map(fn ($p) => [
                'name' => $p['name'] ?? '',
                'label' => $p['label'] ?? $p['name'] ?? '',
                'required' => ! empty($p['required']),
                'description' => $p['description'] ?? '',
                'example' => $p['example'] ?? '',
            ])->values()->all(),
        ];
    }
}
