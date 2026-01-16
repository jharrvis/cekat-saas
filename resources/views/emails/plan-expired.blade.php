<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Berakhir</title>
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
                            style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0;">⚠️ Plan Telah Berakhir</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>

                            <div
                                style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 16px 20px; margin: 24px 0; border-radius: 8px;">
                                <p style="color: #991b1b; font-size: 16px; margin: 0;">
                                    Plan <strong>{{ $oldPlanName }}</strong> Anda telah berakhir.
                                    Akun Anda telah otomatis di-downgrade ke <strong>Free Plan</strong>.
                                </p>
                            </div>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Apa yang berubah:
                            </p>

                            <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; padding-left: 20px;">
                                <li>Kuota pesan telah direset ke batas Free Plan</li>
                                <li>Beberapa fitur premium tidak lagi tersedia</li>
                                <li>Widget chatbot tetap aktif dengan batasan</li>
                            </ul>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 20px 0;">
                                Upgrade kembali untuk menikmati semua fitur premium!
                            </p>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ url('/billing') }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Upgrade Sekarang
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