<x-emails.layout title="Kode Verifikasi" category="Verifikasi Email">
    <x-emails.heading>Kode Verifikasi Email</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 6px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 24px;">
        Masukkan kode berikut untuk memverifikasi email Anda:
    </p>

    <div style="text-align:center;margin:26px 0;">
        <span
            style="display:inline-block;background-color:#18181b;color:#ffffff;font-family:Consolas,Menlo,monospace;font-size:34px;font-weight:700;letter-spacing:10px;padding:18px 30px 18px 40px;border-radius:6px;">{{ $code }}</span>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0 0 6px;">
        Kode berlaku <strong>5 menit</strong>. Minta kode baru bila kedaluwarsa.
    </p>
    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        Tidak meminta kode ini? Abaikan email ini &mdash; akun Anda tidak akan berubah sampai kode dimasukkan.
    </p>
</x-emails.layout>
