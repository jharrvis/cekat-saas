<x-emails.layout title="{{ __('emails.s.setting_diubah') }}" category="Admin · Jejak Audit">
    <x-emails.heading>{{ __('emails.s.setting_diubah') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __('emails.s.setting') }} <strong>{{ ucfirst($group) }}</strong> telah disimpan pada
        {{ now()->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel>
        <x-emails.field label="Oleh" :value="$changedBy?->name ?? 'Sistem'" />
        @if(count($keys))
            <x-emails.field label="Field" :value="implode(', ', $keys)" />
        @endif
    </x-emails.panel>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        {{ __('emails.s.email_ini_dikirim_otomatis_sebagai_jejak_audit_b') }}
    </p>
</x-emails.layout>
