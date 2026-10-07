<x-emails.layout title="{{ __('emails.s.kode_verifikasi') }}" category="Verifikasi Email">
    <x-emails.heading>{{ __('emails.s.kode_verifikasi_email') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 6px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 24px;">
        {{ __('emails.s.masukkan_kode_berikut_untuk_memverifikasi_email') }}
    </p>

    <div style="text-align:center;margin:26px 0;">
        <span
            style="display:inline-block;background-color:#18181b;color:#ffffff;font-family:Consolas,Menlo,monospace;font-size:34px;font-weight:700;letter-spacing:10px;padding:18px 30px 18px 40px;border-radius:6px;">{{ $code }}</span>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0 0 6px;">
        {{ __('emails.s.kode_berlaku') }} <strong>{{ __('emails.s.5_menit') }}</strong>{{ __('emails.s.minta_kode_baru_bila_kedaluwarsa') }}
    </p>
    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        {{ __('emails.s.tidak_meminta_kode_ini_abaikan_email_ini_mdash_a') }}
    </p>
</x-emails.layout>
