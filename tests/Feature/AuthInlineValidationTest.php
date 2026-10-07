<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-08 / finding F-08: auth forms must show server-side,
 * per-field, Indonesian validation errors for ALL invalid fields at once —
 * previously the browsers' native tooltips fired first (no `novalidate`)
 * and the login form had no per-field error blocks at all.
 */
class AuthInlineValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // T-08 ships the Indonesian language files; T-12 flips the default
        // locale application-wide. Tests pin the locale explicitly.
        app()->setLocale('id');
    }

    public function test_register_empty_submit_shows_all_field_errors_in_indonesian(): void
    {
        $response = $this->from('/register')->followingRedirects()->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertOk(); // back on the register page
        $response->assertSee('Nama wajib diisi.');
        $response->assertSee('Email wajib diisi.');
        $response->assertSee('Kata sandi wajib diisi.');
        $response->assertSee('Periksa kembali isian Anda:'); // summary block
    }

    public function test_register_password_mismatch_shows_indonesian_confirmation_error(): void
    {
        $response = $this->from('/register')->followingRedirects()->post('/register', [
            'name' => 'Pengguna Uji',
            'email' => 'uji-' . uniqid() . '@test.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'beda12345',
        ]);

        $response->assertSee('Konfirmasi kata sandi tidak cocok.');
    }

    public function test_login_empty_submit_shows_inline_errors(): void
    {
        $response = $this->from('/login')->followingRedirects()->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSee('Email wajib diisi.');
        $response->assertSee('Kata sandi wajib diisi.');
    }

    public function test_auth_forms_disable_native_browser_validation(): void
    {
        $this->get('/register')->assertSee('novalidate');
        $this->get('/login')->assertSee('novalidate');
        $this->get('/forgot-password')->assertSee('novalidate');
    }
}
