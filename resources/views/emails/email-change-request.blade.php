<x-emails.layout title="Permintaan Perubahan Email" category="Perubahan Email">
    <x-emails.heading>Permintaan Perubahan Email</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Kami menerima permintaan untuk mengubah email akun Cekat Anda dari
        <strong>{{ $oldEmail }}</strong> menjadi <strong>{{ $newEmail }}</strong>.
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Perubahan <strong>belum aktif</strong> sampai dikonfirmasi melalui inbox email baru.
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>Bukan Anda?</strong> Jangan konfirmasi permintaan ini dan segera amankan akun Anda
            dengan mengubah password melalui halaman Pengaturan.
        </p>
    </x-emails.panel>
</x-emails.layout>
