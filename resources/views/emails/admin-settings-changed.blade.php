<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Diubah</title>
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
                            style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0;">⚙️ Setting Diubah</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Setting <strong>{{ ucfirst($group) }}</strong> telah disimpan pada
                                {{ now()->format('d M Y H:i') }} WIB.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #eef2ff; border: 1px solid #c7d2fe; border-radius: 12px; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 20px 24px;">
                                        <p style="color: #3730a3; font-size: 14px; margin: 0 0 8px;">
                                            <strong>Oleh:</strong> {{ $changedBy?->name ?? 'Sistem' }}
                                        </p>
                                        @if(count($keys))
                                            <p style="color: #3730a3; font-size: 14px; margin: 0;">
                                                <strong>Field:</strong> {{ implode(', ', $keys) }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
                                Email ini dikirim otomatis sebagai jejak audit. Bila perubahan ini bukan
                                dilakukan oleh Anda, segera periksa akun dan sesi admin Anda.
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
