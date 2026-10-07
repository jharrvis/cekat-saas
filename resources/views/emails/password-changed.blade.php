<x-emails.layout title="{{ __('emails.s.password_diubah') }}" category="Keamanan Akun">
    <x-emails.heading>{{ __('emails.s.password_akun_diubah') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.password_akun_cekat_anda_baru_saja_diubah_melalu') }} <strong>{{ $changedVia }}</strong>
        pada {{ now()->format('d M Y H:i') }} WIB{{ $ip ? ' (IP: ' . $ip . ')' : '' }}.
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>{{ __('emails.s.bukan_anda') }}</strong> {{ __('emails.s.segera_atur_ulang_password_melalui_halaman_ldquo') }}
        </p>
    </x-emails.panel>
</x-emails.layout>
