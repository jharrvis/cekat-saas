<x-emails.layout title="Konfirmasi Perubahan Email" category="Perubahan Email">
    <x-emails.heading>Konfirmasi Perubahan Email</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Anda meminta perubahan email akun Cekat dari
        <strong>{{ $oldEmail }}</strong> menjadi <strong>{{ $newEmail }}</strong>.
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Klik tombol di bawah untuk mengonfirmasi. Perubahan hanya aktif setelah dikonfirmasi dari inbox ini.
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="$confirmationUrl">Konfirmasi Perubahan Email</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:8px 0 0;">
        Link berlaku 24 jam. Jika Anda tidak mengajukan perubahan ini, abaikan email ini &mdash;
        email Anda tidak akan berubah.
    </p>
</x-emails.layout>
