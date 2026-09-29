<x-emails.layout title="Plan Akan Berakhir" category="Periode Langganan">
    <x-emails.heading>Plan Akan Berakhir</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            Plan <strong>{{ $user->plan->name ?? 'Premium' }}</strong> Anda akan berakhir dalam
            <strong>{{ $daysLeft }} hari</strong>.
        </p>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Jika tidak diperpanjang, akun Anda akan otomatis di-downgrade ke <strong>Free Plan</strong> dengan batasan:
    </p>

    <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
        <li>Kuota pesan terbatas</li>
        <li>Widget chatbot berkurang</li>
        <li>Fitur premium tidak tersedia</li>
    </ul>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/billing')">Perpanjang Sekarang</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:8px 0 0;">
        Terima kasih telah menggunakan Cekat.
    </p>
</x-emails.layout>
