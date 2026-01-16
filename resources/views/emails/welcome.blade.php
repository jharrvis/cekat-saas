<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang</title>
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
                            style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0;">🎉 Selamat Datang!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                Selamat datang di <strong>Cekat.ai</strong>! Akun Anda telah berhasil dibuat.
                            </p>

                            <div style="background-color: #f3e8ff; border-radius: 12px; padding: 24px; margin: 24px 0;">
                                <h3 style="color: #7c3aed; font-size: 18px; margin: 0 0 16px;">
                                    🚀 Mulai dalam 3 langkah mudah:
                                </h3>
                                <ol
                                    style="color: #374151; font-size: 14px; line-height: 2; margin: 0; padding-left: 20px;">
                                    <li>Buat chatbot pertama Anda di menu <strong>Chatbots</strong></li>
                                    <li>Tambahkan Knowledge Base untuk melatih AI</li>
                                    <li>Pasang widget di website Anda</li>
                                </ol>
                            </div>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Anda saat ini menggunakan <strong>Free Plan</strong>. Upgrade untuk mendapatkan lebih
                                banyak fitur!
                            </p>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ url('/dashboard') }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; margin-right: 12px;">
                                    Buka Dashboard
                                </a>
                            </div>

                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                                Butuh bantuan? Hubungi kami di <a href="mailto:support@cekat.ai"
                                    style="color: #7c3aed;">support@cekat.ai</a>
                            </p>
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