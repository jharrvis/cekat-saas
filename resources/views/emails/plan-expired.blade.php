<x-emails.layout title="{{ __('emails.s.plan_berakhir') }}" category="Periode Langganan">
    <x-emails.heading>{{ __('emails.s.plan_telah_berakhir') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    <x-emails.panel tone="danger">
        <p style="font-size:14px;line-height:1.6;color:#7f1d1d;margin:0;">
            {{ __('emails.s.plan') }} <strong>{{ $oldPlanName }}</strong> {{ __('emails.s.anda_telah_berakhir_akun_anda_telah_otomatis_di') }} <strong>{{ __('emails.s.free_plan') }}</strong>.
        </p>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.apa_yang_berubah') }}
    </p>

    <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
        <li>{{ __('emails.s.kuota_pesan_telah_direset_ke_batas_free_plan') }}</li>
        <li>{{ __('emails.s.beberapa_fitur_premium_tidak_lagi_tersedia') }}</li>
        <li>{{ __('emails.s.widget_chatbot_tetap_aktif_dengan_batasan') }}</li>
    </ul>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:16px 0 4px;">
        {{ __('emails.s.upgrade_kembali_untuk_menikmati_semua_fitur_prem') }}
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/billing')">{{ __('emails.s.upgrade_sekarang') }}</x-emails.button>
    </div>
</x-emails.layout>
