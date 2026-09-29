<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftar Baru</title>
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
                            style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0;">🆕 Pendaftar Baru</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Akun baru telah mendaftar dan memverifikasi email pada
                                {{ $user->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }} WIB.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 12px; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 20px 24px;">
                                        <p style="color: #5b21b6; font-size: 14px; margin: 0 0 8px;">
                                            <strong>Nama:</strong> {{ $user->name }}
                                        </p>
                                        <p style="color: #5b21b6; font-size: 14px; margin: 0 0 8px;">
                                            <strong>Email:</strong> {{ $user->email }}
                                        </p>
                                        <p style="color: #5b21b6; font-size: 14px; margin: 0;">
                                            <strong>ID:</strong> #{{ $user->id }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ route('admin.users') }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Lihat Daftar Pengguna
                                </a>
                            </div>
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
