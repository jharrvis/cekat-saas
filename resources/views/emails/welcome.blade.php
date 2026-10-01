<x-emails.layout title="Selamat Datang" category="Selamat Datang">
    <x-emails.heading>Halo {{ $user->name }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        Selamat datang di Cekat. Akun Anda telah berhasil dibuat dan siap digunakan.
    </p>

    <x-emails.panel>
        <div
            style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin-bottom:12px;">
            Mulai dalam 3 langkah</div>
        <ol style="color:#3f3f46;font-size:14px;line-height:2;padding-left:20px;margin:0;">
            <li>Buat chatbot pertama Anda di menu <strong>Chatbots</strong></li>
            <li>Tambahkan Knowledge Base untuk melatih AI</li>
            <li>Pasang widget di website Anda</li>
        </ol>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 24px;">
        Anda saat ini menggunakan <strong>Free Plan</strong>. Upgrade untuk mendapatkan lebih banyak fitur.
    </p>

    <div style="margin:28px 0;">
        <x-emails.button :href="url('/dashboard')">Buka Dashboard</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        Butuh bantuan? Hubungi kami di <a href="mailto:support@cekat.ai"
            style="color:#18181b;text-decoration:underline;">support@cekat.ai</a>
    </p>
</x-emails.layout>
