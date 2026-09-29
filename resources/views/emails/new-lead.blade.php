<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Baru</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0;">🆕 Lead Baru Masuk</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                Widget <strong>{{ $widget->name }}</strong> baru saja menangkap lead dari percakapan
                                pada {{ ($session?->created_at ?? now())->format('d M Y H:i') }} WIB.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 20px 24px;">
                                        @if($lead['name'] ?? null)
                                            <p style="color: #166534; font-size: 14px; margin: 0 0 8px;">
                                                <strong>Nama:</strong> {{ $lead['name'] }}
                                            </p>
                                        @endif
                                        @if($lead['email'] ?? null)
                                            <p style="color: #166534; font-size: 14px; margin: 0 0 8px;">
                                                <strong>Email:</strong> {{ $lead['email'] }}
                                            </p>
                                        @endif
                                        @if($lead['phone'] ?? null)
                                            <p style="color: #166534; font-size: 14px; margin: 0 0 8px;">
                                                <strong>Telepon:</strong> {{ $lead['phone'] }}
                                            </p>
                                        @endif
                                        @if(!($lead['name'] ?? null) && !($lead['email'] ?? null) && !($lead['phone'] ?? null))
                                            <p style="color: #166534; font-size: 14px; margin: 0;">
                                                Data kontak terlampir di percakapan.
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ url('/leads') }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Lihat &amp; Follow Up Lead
                                </a>
                            </div>

                            <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
                                Balas cepat meningkatkan peluang konversi. Lead ini juga tersimpan di menu
                                <strong>Lead Collection</strong> dasbor Anda.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="background-color: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} Cekat - AI Customer Service Platform
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
