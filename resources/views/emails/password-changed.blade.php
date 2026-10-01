<x-emails.layout title="Password Diubah" category="Keamanan Akun">
    <x-emails.heading>Password Akun Diubah</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Password akun Cekat Anda baru saja diubah melalui <strong>{{ $changedVia }}</strong>
        pada {{ now()->format('d M Y H:i') }} WIB{{ $ip ? ' (IP: ' . $ip . ')' : '' }}.
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            <strong>Bukan Anda?</strong> Segera atur ulang password melalui halaman &ldquo;Lupa sandi?&rdquo;
            dan hubungi support.
        </p>
    </x-emails.panel>
</x-emails.layout>
