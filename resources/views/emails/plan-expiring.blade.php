<x-emails.layout title="{{ __('emails.s.plan_akan_berakhir') }}" category="Periode Langganan">
    <x-emails.heading>{{ __('emails.s.plan_akan_berakhir') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    <x-emails.panel tone="alert">
        <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
            {{ __('emails.s.plan') }} <strong>{{ $user->plan->name ?? 'Premium' }}</strong> {{ __('emails.s.anda_akan_berakhir_dalam') }}
            <strong>{{ $daysLeft }} hari</strong>.
        </p>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.jika_tidak_diperpanjang_akun_anda_akan_otomatis') }} <strong>{{ __('emails.s.free_plan') }}</strong> {{ __('emails.s.dengan_batasan') }}
    </p>

    <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
        <li>{{ __('emails.s.kuota_pesan_terbatas') }}</li>
        <li>{{ __('emails.s.widget_chatbot_berkurang') }}</li>
        <li>{{ __('emails.s.fitur_premium_tidak_tersedia') }}</li>
    </ul>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/billing')">{{ __('emails.s.perpanjang_sekarang') }}</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:8px 0 0;">
        {{ __('emails.s.terima_kasih_telah_menggunakan_cekat') }}
    </p>
</x-emails.layout>
