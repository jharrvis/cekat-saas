<x-emails.layout title="{{ __('emails.s.lead_baru') }}" category="Notifikasi Lead">
    <x-emails.heading>{{ __('emails.s.lead_baru_masuk') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('agents.s.widget') }} <strong>{{ $widget->name }}</strong> baru saja menangkap lead dari percakapan
        pada {{ ($session?->created_at ?? now())->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel>
        @if($lead['name'] ?? null)
            <x-emails.field label="Nama" :value="$lead['name']" />
        @endif
        @if($lead['email'] ?? null)
            <x-emails.field label="Email" :value="$lead['email']" />
        @endif
        @if($lead['phone'] ?? null)
            <x-emails.field label="Telepon" :value="$lead['phone']" />
        @endif
        @if($location = trim(implode(', ', array_filter([
            $session?->location_data['city'] ?? null,
            $session?->location_data['region'] ?? null,
            $session?->location_data['country'] ?? null,
        ]))))
            <x-emails.field label="Lokasi" :value="$location" />
        @endif
        @if($session?->ip_address)
            <x-emails.field label="IP Address" :value="$session->ip_address" :mono="true" />
        @endif
        @php
            $device = $session
                ? (\App\Support\VisitorGeo::describeAgent($session->user_agent) ?: ucfirst((string) ($session->device_type ?? '')))
                : '';
        @endphp
        @if($device !== '')
            <x-emails.field label="Perangkat" :value="$device" />
        @endif
        @if($session?->source_url)
            <x-emails.field label="Halaman" :value="\Illuminate\Support\Str::limit($session->source_url, 120)" :mono="true" />
        @endif
        @if($session?->referer_url)
            <x-emails.field label="Referrer" :value="\Illuminate\Support\Str::limit($session->referer_url, 120)" :mono="true" />
        @endif
        @unless(($lead['name'] ?? null) || ($lead['email'] ?? null) || ($lead['phone'] ?? null))
            <p style="font-size:14px;line-height:1.6;color:#3f3f46;margin:0;">
                {{ __('emails.s.data_kontak_terlampir_di_percakapan') }}
            </p>
        @endunless
    </x-emails.panel>

    @if($session?->summary)
        <div style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin:26px 0 8px;">
            {{ __('emails.s.ringkasan_percakapan') }}</div>
        <p style="font-size:15px;line-height:1.7;color:#18181b;background-color:#fafafa;border:1px solid #e4e4e7;border-radius:8px;padding:18px 20px;margin:0;">
            {{ $session->summary }}
        </p>
    @else
        @php
            $recent = $session ? $session->messages()->orderByDesc('id')->take(3)->get()->reverse() : collect();
        @endphp
        @if($recent->isNotEmpty())
            <div style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin:26px 0 8px;">
                {{ __('emails.s.potongan_percakapan_terakhir') }}</div>
            <x-emails.panel>
                @foreach($recent as $msg)
                    <p style="font-size:14px;line-height:1.6;color:#3f3f46;margin:0 0 {{ $loop->last ? '0' : '10px' }};">
                        <strong style="color:#18181b;">{{ $msg->role === 'user' ? 'Pengunjung' : 'Layanan' }}:</strong>
                        {{ \Illuminate\Support\Str::limit($msg->content, 220) }}
                    </p>
                @endforeach
            </x-emails.panel>
        @endif
    @endif

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/leads')">{{ __('emails.s.lihat_amp_follow_up_lead') }}</x-emails.button>
    </div>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:8px 0 0;">
        {{ __('emails.s.balas_cepat_meningkatkan_peluang_konversi_lead_i') }}
        <strong>{{ __('emails.s.lead_collection') }}</strong> {{ __('emails.s.dasbor_anda') }}
    </p>
</x-emails.layout>
