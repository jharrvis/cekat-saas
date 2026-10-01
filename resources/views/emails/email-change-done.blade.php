<x-emails.layout title="Email Akun Berubah" category="Perubahan Email">
    <x-emails.heading>Email Akun Anda Berubah</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Email akun Cekat Anda resmi diubah dari <strong>{{ $oldEmail }}</strong> menjadi
        <strong>{{ $newEmail }}</strong> pada {{ now()->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>Bukan Anda?</strong> Segera hubungi support atau amankan akun Anda.
        </p>
    </x-emails.panel>
</x-emails.layout>
