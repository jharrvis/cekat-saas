<x-emails.layout title="{{ __('emails.s.email_akun_berubah') }}" category="Perubahan Email">
    <x-emails.heading>{{ __('emails.s.email_akun_anda_berubah') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.email_akun_cekat_anda_resmi_diubah_dari') }} <strong>{{ $oldEmail }}</strong> {{ __('emails.s.menjadi') }}
        <strong>{{ $newEmail }}</strong> pada {{ now()->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>{{ __('emails.s.bukan_anda') }}</strong> {{ __('emails.s.segera_hubungi_support_atau_amankan_akun_anda') }}
        </p>
    </x-emails.panel>
</x-emails.layout>
