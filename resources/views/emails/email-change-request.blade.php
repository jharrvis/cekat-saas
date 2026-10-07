<x-emails.layout title="{{ __('emails.s.permintaan_perubahan_email') }}" category="Perubahan Email">
    <x-emails.heading>{{ __('emails.s.permintaan_perubahan_email') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.kami_menerima_permintaan_untuk_mengubah_email_ak') }}
        <strong>{{ $oldEmail }}</strong> {{ __('emails.s.menjadi') }} <strong>{{ $newEmail }}</strong>.
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.perubahan') }} <strong>{{ __('emails.s.belum_aktif') }}</strong> {{ __('emails.s.sampai_dikonfirmasi_melalui_inbox_email_baru') }}
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>{{ __('emails.s.bukan_anda') }}</strong> {{ __('emails.s.jangan_konfirmasi_permintaan_ini_dan_segera_aman') }}
        </p>
    </x-emails.panel>
</x-emails.layout>
