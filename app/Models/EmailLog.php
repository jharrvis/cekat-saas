<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    /**
     * Human labels for every outbound email category in the app.
     */
    public const CATEGORIES = [
        'otp' => 'Verifikasi OTP',
        'welcome' => 'Selamat datang',
        'new-lead' => 'Lead baru',
        'password-changed' => 'Perubahan password',
        'email-change-confirm' => 'Konfirmasi ganti email',
        'email-change-request' => 'Peringatan ganti email',
        'email-change-done' => 'Info email berubah',
        'payment' => 'Pembayaran',
        'plan-expiring' => 'Pengingat plan',
        'plan-expired' => 'Plan berakhir',
        'admin-signup' => 'Pendaftar baru',
        'admin-settings' => 'Perubahan setting',
        'account-suspended' => 'Akun disuspend',
        'campaign-newsletter' => 'Newsletter',
        'campaign-announcement' => 'Pengumuman',
        'test' => 'Email uji',
    ];

    protected $table = 'email_logs';

    protected $fillable = [
        'category',
        'mailable',
        'recipient',
        'subject',
        'status',
        'error',
        'body',
        'meta',
        'campaign_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category] ?? $category;
    }
}
