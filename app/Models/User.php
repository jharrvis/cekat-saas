<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'locale',
        'pending_email',
        'email_verified_at',
        'password',
        'google_id',
        'avatar',
        'role',
        'status',
        'suspended_at',
        'suspended_reason',
        'plan_id',
        'plan_expires_at',
        'monthly_message_used',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expires_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Get the widgets for the user.
     */
    public function widgets()
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Personal API keys for the public read API (/api/v1).
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the user's plan.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Send the email verification code (6-digit OTP, branded mailable) -
     * the code is entered in the blocking dashboard modal.
     */
    public function sendEmailVerificationNotification()
    {
        $code = app(\App\Services\Auth\EmailOtpService::class)->generate($this);

        \App\Services\Email\EmailSender::send($this->email, new \App\Mail\EmailOtp($this, $code), 'otp', [
            'user_id' => $this->id,
        ]);
    }

    /**
     * Whether the user can access Lead Collection features (Pro and above).
     */
    public function canUseLeads(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(\App\Services\Billing\PlanLimitService::class)->feature($this, 'leads');
    }

    /**
     * Whether the user can use the public read API with an API key
     * (gated by the plan's api_access feature flag).
     */
    public function canUseApi(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(\App\Services\Billing\PlanLimitService::class)->feature($this, 'api_access');
    }

    /**
     * Whether the user can access the WhatsApp gateway (Pro and above).
     */
    public function canUseWhatsApp(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(\App\Services\Billing\PlanLimitService::class)->feature($this, 'whatsapp');
    }

    /**
     * Get the user's WhatsApp devices.
     */
    public function whatsappDevices()
    {
        return $this->hasMany(WhatsAppDevice::class);
    }

    /**
     * Get the user's AI agents.
     */
    public function aiAgents()
    {
        return $this->hasMany(AiAgent::class);
    }

    /**
     * Get active AI agents for the user.
     */
    public function activeAiAgents()
    {
        return $this->aiAgents()->where('is_active', true);
    }
}
