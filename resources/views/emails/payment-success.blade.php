<x-emails.layout title="Pembayaran Berhasil" category="Pembayaran">
    <x-emails.heading>Pembayaran Berhasil</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Terima kasih atas pembayaran Anda. Plan Anda telah berhasil diaktifkan.
    </p>

    <x-emails.panel>
        <div
            style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin-bottom:14px;">
            Detail Transaksi</div>
        <x-emails.field label="Order ID" :value="$transaction->order_id" :mono="true" />
        <x-emails.field label="Plan" :value="$transaction->plan->name ?? '-'" />
        <x-emails.field label="Jumlah" :value="'Rp ' . number_format($transaction->amount, 0, ',', '.')" />
        <x-emails.field label="Metode Pembayaran" :value="ucfirst($transaction->payment_type ?? '-')" />
        <x-emails.field label="Tanggal"
            :value="$transaction->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i')" />
        <x-emails.field label="Aktif Sampai" :value="$user->plan_expires_at?->format('d M Y') ?? '-'" />
    </x-emails.panel>

    <div style="margin:28px 0 6px;">
        <x-emails.button :href="url('/dashboard')">Buka Dashboard</x-emails.button>
    </div>
</x-emails.layout>
