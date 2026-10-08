{{--
    Invoice PDF template (dompdf — plain CSS only, no Tailwind).
    One invoice per transaction; combined downloads get one page each.
    Rendered in the account owner's locale via InvoiceService.
--}}
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .invoice { page-break-after: always; }
        .invoice:last-child { page-break-after: auto; }
        .header { border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 20px; }
        .brand { font-size: 22px; font-weight: bold; color: #0f766e; }
        .brand small { display: block; font-size: 11px; font-weight: normal; color: #6b7280; margin-top: 2px; }
        .title { float: right; text-align: right; }
        .title .doc { font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #111827; }
        .paid { display: inline-block; margin-top: 6px; padding: 3px 12px; border: 2px solid #0d9488; color: #0d9488; font-weight: bold; border-radius: 4px; letter-spacing: 1px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.meta td { vertical-align: top; padding: 2px 0; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th { background: #f0fdfa; border: 1px solid #d1d5db; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items td { border: 1px solid #d1d5db; padding: 10px 8px; }
        .right { text-align: right; }
        table.items tr.total td { font-weight: bold; font-size: 13px; background: #f9fafb; }
        .footer { margin-top: 28px; border-top: 1px solid #e5e7eb; padding-top: 10px; color: #6b7280; font-size: 10.5px; line-height: 1.5; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
@foreach($transactions as $tx)
    <div class="invoice">
        <div class="header clearfix">
            <div class="title">
                <div class="doc">{{ __('billing.s.invoice_judul', [], $locale) }}</div>
                <div class="paid">{{ __('billing.s.invoice_lunas', [], $locale) }}</div>
            </div>
            <div class="brand">Cekat.biz.id
                <small>support@cekat.biz.id — https://cekat.biz.id</small>
            </div>
        </div>

        <table class="meta">
            <tr>
                <td style="width: 50%;">
                    <div class="label">{{ __('billing.s.invoice_kepada', [], $locale) }}</div>
                    <strong>{{ $user->name }}</strong><br>{{ $user->email }}
                </td>
                <td style="width: 50%;">
                    <div class="label">{{ __('billing.s.invoice_nomor', [], $locale) }}</div>
                    {{ \App\Services\Billing\InvoiceService::number($tx) }}<br>
                    <span class="label">{{ __('billing.s.invoice_order_id', [], $locale) }}</span><br>
                    {{ $tx->order_id }}<br>
                    <span class="label">{{ __('billing.s.invoice_tanggal_bayar', [], $locale) }}</span><br>
                    {{ ($tx->paid_at ?? $tx->created_at)->format('d M Y H:i') }}<br>
                    <span class="label">{{ __('billing.s.invoice_metode', [], $locale) }}</span><br>
                    {{ $tx->payment_type ?: '-' }}
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('billing.s.invoice_deskripsi', [], $locale) }}</th>
                    <th class="right" style="width: 30%;">{{ __('billing.s.jumlah', [], $locale) }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('billing.s.invoice_plan_periode', ['plan' => $tx->plan->name ?? '-'], $locale) }}</td>
                    <td class="right">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>{{ __('billing.s.invoice_total', [], $locale) }}</td>
                    <td class="right">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">{{ __('billing.s.invoice_catatan', [], $locale) }}</div>
    </div>
@endforeach
</body>
</html>
