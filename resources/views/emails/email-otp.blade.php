<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi</title>
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
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0;">🔐 Kode Verifikasi</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 8px;">
                                Masukkan kode berikut untuk memverifikasi email Anda:
                            </p>

                            <div style="text-align: center; margin: 28px 0;">
                                <span
                                    style="display: inline-block; background-color: #f0fdf4; border: 2px dashed #10b981; border-radius: 12px; padding: 18px 36px; font-size: 38px; font-weight: 700; letter-spacing: 12px; color: #065f46;">
                                    {{ $code }}
                                </span>
                            </div>

                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0 0 4px;">
                                Kode berlaku <strong>5 menit</strong>. Minta kode baru bila kedaluwarsa.
                            </p>
                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                                Tidak meminta kode ini? Abaikan email ini — akun Anda tidak akan
                                berubah sampai kode dimasukkan.
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
