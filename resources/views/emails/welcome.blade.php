<x-emails.layout title="{{ __('emails.s.selamat_datang') }}" category="Selamat Datang">
    <x-emails.heading>Halo {{ $user->name }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 16px;">
        {{ __('emails.s.selamat_datang_di_cekat_akun_anda_telah_berhasil') }}
    </p>

    <x-emails.panel>
        <div
            style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin-bottom:12px;">
            {{ __('emails.s.mulai_dalam_3_langkah') }}</div>
        <ol style="color:#3f3f46;font-size:14px;line-height:2;padding-left:20px;margin:0;">
            <li>{{ __('emails.s.buat_chatbot_pertama_anda_di_menu') }} <strong>{{ __('emails.s.chatbots') }}</strong></li>
            <li>{{ __('emails.s.tambahkan_knowledge_base_untuk_melatih_ai') }}</li>
            <li>{{ __('emails.s.pasang_widget_di_website_anda') }}</li>
        </ol>
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 24px;">
        {{ __('emails.s.anda_saat_ini_menggunakan') }} <strong>{{ __('emails.s.free_plan') }}</strong>{{ __('emails.s.upgrade_untuk_mendapatkan_lebih_banyak_fitur') }}
    </p>

    <div style="margin:28px 0;">
        <x-emails.button :href="url('/dashboard')">{{ __('emails.s.buka_dashboard') }}</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        {{ __('emails.s.butuh_bantuan_hubungi_kami_di') }} <a href="mailto:support@cekat.biz.id"
            style="color:#18181b;text-decoration:underline;">support@cekat.biz.id</a>
    </p>
</x-emails.layout>
