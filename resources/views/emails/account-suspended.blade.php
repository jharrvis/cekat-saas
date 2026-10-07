<x-emails.layout title="Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }}" category="Status Akun">
    <x-emails.heading>Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    @if($type === 'banned')
        <x-emails.panel tone="danger">
            <p style="font-size:14px;line-height:1.6;color:#7f1d1d;margin:0;">
                {{ __('emails.s.akun_anda_telah') }} <strong>{{ __('emails.s.diblokir_secara_permanen') }}</strong> {{ __('emails.s.karena_melanggar_ketentuan_layanan') }}
            </p>
        </x-emails.panel>

        <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
            {{ __('emails.s.dampak_pemblokiran') }}
        </p>
        <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
            <li>{{ __('emails.s.anda_tidak_dapat_mengakses_dashboard') }}</li>
            <li>{{ __('emails.s.semua_widget_chatbot_dinonaktifkan') }}</li>
            <li>{{ __('emails.s.data_akun_tetap_tersimpan') }}</li>
        </ul>
    @else
        <x-emails.panel tone="alert">
            <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
                {{ __('emails.s.akun_anda_telah') }} <strong>{{ __('emails.s.ditangguhkan_sementara') }}</strong>.
            </p>
        </x-emails.panel>

        @if($reason)
            <x-emails.panel>
                <x-emails.field label="Alasan" :value="$reason" />
            </x-emails.panel>
        @endif

        <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
            {{ __('emails.s.selama_penangguhan') }}
        </p>
        <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
            <li>{{ __('emails.s.anda_tidak_dapat_mengakses_dashboard') }}</li>
            <li>{{ __('emails.s.widget_chatbot_dinonaktifkan_sementara') }}</li>
            <li>{{ __('emails.s.akun_dapat_diaktifkan_kembali_setelah_masalah_di') }}</li>
        </ul>
    @endif

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:20px 0 4px;">
        {{ __('emails.s.jika_anda_merasa_ini_adalah_kesalahan_silakan_hu') }}
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button href="mailto:support@cekat.biz.id">{{ __('auth.s.hubungi_support') }}</x-emails.button>
    </div>
</x-emails.layout>
