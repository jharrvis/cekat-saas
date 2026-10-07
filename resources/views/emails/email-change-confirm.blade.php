<x-emails.layout title="{{ __('emails.s.konfirmasi_perubahan_email') }}" category="Perubahan Email">
    <x-emails.heading>{{ __('emails.s.konfirmasi_perubahan_email') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.anda_meminta_perubahan_email_akun_cekat_dari') }}
        <strong>{{ $oldEmail }}</strong> {{ __('emails.s.menjadi') }} <strong>{{ $newEmail }}</strong>.
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.klik_tombol_di_bawah_untuk_mengonfirmasi_perubah') }}
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="$confirmationUrl">{{ __('emails.s.konfirmasi_perubahan_email') }}</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:8px 0 0;">
        {{ __('emails.s.link_berlaku_24_jam_jika_anda_tidak_mengajukan_p') }}
    </p>
</x-emails.layout>
