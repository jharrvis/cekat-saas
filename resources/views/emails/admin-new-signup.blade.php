<x-emails.layout title="{{ __('emails.s.pendaftar_baru') }}" category="Admin · Pendaftar Baru">
    <x-emails.heading>{{ __('emails.s.pendaftar_baru') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Akun baru telah mendaftar dan memverifikasi email pada
        {{ $user->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }} WIB.
    </p>

    <x-emails.panel>
        <x-emails.field label="Nama" :value="$user->name" />
        <x-emails.field label="Email" :value="$user->email" />
        <x-emails.field label="ID" :value="'#' . $user->id" />
    </x-emails.panel>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="route('admin.users')">{{ __('emails.s.lihat_daftar_pengguna') }}</x-emails.button>
    </div>
</x-emails.layout>
