<x-emails.layout title="Plan Berakhir" category="Periode Langganan">
    <x-emails.heading>Plan Telah Berakhir</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    <x-emails.panel tone="danger">
        <p style="font-size:14px;line-height:1.6;color:#7f1d1d;margin:0;">
            Plan <strong>{{ $oldPlanName }}</strong> Anda telah berakhir. Akun Anda telah otomatis
            di-downgrade ke <strong>Free Plan</strong>.
        </p>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Apa yang berubah:
    </p>

    <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
        <li>Kuota pesan telah direset ke batas Free Plan</li>
        <li>Beberapa fitur premium tidak lagi tersedia</li>
        <li>Widget chatbot tetap aktif dengan batasan</li>
    </ul>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:16px 0 4px;">
        Upgrade kembali untuk menikmati semua fitur premium.
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/billing')">Upgrade Sekarang</x-emails.button>
    </div>
</x-emails.layout>
