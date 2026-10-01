<x-emails.layout title="Setting Diubah" category="Admin · Jejak Audit">
    <x-emails.heading>Setting Diubah</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Setting <strong>{{ ucfirst($group) }}</strong> telah disimpan pada
        {{ now()->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel>
        <x-emails.field label="Oleh" :value="$changedBy?->name ?? 'Sistem'" />
        @if(count($keys))
            <x-emails.field label="Field" :value="implode(', ', $keys)" />
        @endif
    </x-emails.panel>

    <p style="font-size:13px;line-height:1.65;color:#71717a;margin:0;">
        Email ini dikirim otomatis sebagai jejak audit. Bila perubahan ini bukan dilakukan oleh Anda,
        segera periksa akun dan sesi admin Anda.
    </p>
</x-emails.layout>
