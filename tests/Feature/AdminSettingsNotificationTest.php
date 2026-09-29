<?php

namespace Tests\Feature;

use App\Events\AdminSettingsChanged;
use App\Mail\AdminSettingsChangedNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Audit email: whenever an admin saves system settings, the configured
 * ADMIN_NOTIFY_EMAIL receives a notice (first admin as fallback).
 */
class AdminSettingsNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_change_notifies_configured_admin_email(): void
    {
        config(['mail.admin_notify' => 'root@test.id']);
        Mail::fake();

        event(new AdminSettingsChanged('general', null, ['site_name', 'support_email']));

        Mail::assertSent(
            AdminSettingsChangedNotice::class,
            fn ($m) => $m->hasTo('root@test.id')
                && $m->group === 'general'
                && $m->keys === ['site_name', 'support_email']
        );
    }

    public function test_falls_back_to_first_admin_account(): void
    {
        config(['mail.admin_notify' => null]);
        Mail::fake();

        $admin = User::create([
            'name' => 'Primary Admin',
            'email' => 'primary-admin@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        event(new AdminSettingsChanged('limits', $admin->id, ['max_upload_size_mb']));

        Mail::assertSent(
            AdminSettingsChangedNotice::class,
            fn ($m) => $m->hasTo('primary-admin@test.id') && $m->group === 'limits'
        );
    }

    public function test_no_recipient_means_no_mail(): void
    {
        config(['mail.admin_notify' => null]);
        Mail::fake();

        event(new AdminSettingsChanged('api', null, []));

        Mail::assertNotSent(AdminSettingsChangedNotice::class);
    }
}
