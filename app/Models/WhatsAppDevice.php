<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppDevice extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_devices';

    protected $fillable = [
        'user_id',
        'widget_id',
        'fonnte_device_id',
        'fonnte_device_token',
        'phone_number',
        'device_name',
        'status',
        'connected_at',
        'disconnected_at',
        'plan',
        'messages_sent',
        'messages_received',
        'plan_expires_at',
        'settings',
        'is_active',
        'last_error',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'plan_expires_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the widget linked to this device.
     */
    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get all WhatsApp messages for this device.
     */
    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_device_id');
    }

    /**
     * Check if device is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Check if device is active and ready.
     */
    public function isReady(): bool
    {
        return $this->is_active && $this->isConnected();
    }

    /**
     * Get formatted phone number.
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (empty($this->phone_number)) {
            return '-';
        }

        // Format Indonesian phone: 628xxx -> +62 8xxx
        $phone = $this->phone_number;
        if (str_starts_with($phone, '62')) {
            return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Scope: Only active devices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only connected devices.
     */
    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    /**
     * Scope: Ready for messaging (active + connected).
     */
    public function scopeReady($query)
    {
        return $query->active()->connected();
    }
}
