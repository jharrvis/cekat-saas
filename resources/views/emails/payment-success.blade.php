<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0;">✅ Pembayaran Berhasil!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                Terima kasih atas pembayaran Anda. Plan Anda telah berhasil diaktifkan!
                            </p>

                            <!-- Invoice Box -->
                            <div style="background-color: #f9fafb; border-radius: 12px; padding: 24px; margin: 24px 0;">
                                <h3
                                    style="color: #374151; font-size: 16px; margin: 0 0 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px;">
                                    Detail Transaksi
                                </h3>
                                <table width="100%" cellspacing="0" cellpadding="8">
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Order ID</td>
                                        <td
                                            style="color: #374151; font-size: 14px; font-weight: 600; text-align: right;">
                                            {{ $transaction->order_id }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Plan</td>
                                        <td
                                            style="color: #374151; font-size: 14px; font-weight: 600; text-align: right;">
                                            {{ $transaction->plan->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Jumlah</td>
                                        <td
                                            style="color: #374151; font-size: 14px; font-weight: 600; text-align: right;">
                                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Metode Pembayaran</td>
                                        <td
                                            style="color: #374151; font-size: 14px; font-weight: 600; text-align: right; text-transform: capitalize;">
                                            {{ $transaction->payment_type ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Tanggal</td>
                                        <td
                                            style="color: #374151; font-size: 14px; font-weight: 600; text-align: right;">
                                            {{ $transaction->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Aktif Sampai</td>
                                        <td
                                            style="color: #10b981; font-size: 14px; font-weight: 600; text-align: right;">
                                            {{ $user->plan_expires_at?->format('d M Y') ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ url('/dashboard') }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Buka Dashboard
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background-color: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} Cekat.ai - AI Customer Service Platform
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>