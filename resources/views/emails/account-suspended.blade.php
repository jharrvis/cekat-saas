<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        @if($type === 'banned')
                            <td style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 40px 30px; text-align: center;">
                                <h1 style="color: #ffffff; font-size: 28px; margin: 0;">🚫 Akun Diblokir</h1>
                            </td>
                        @else
                            <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 40px 30px; text-align: center;">
                                <h1 style="color: #ffffff; font-size: 28px; margin: 0;">⚠️ Akun Ditangguhkan</h1>
                            </td>
                        @endif
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Halo <strong>{{ $user->name }}</strong>,
                            </p>
                            
                            @if($type === 'banned')
                                <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 16px 20px; margin: 24px 0; border-radius: 8px;">
                                    <p style="color: #991b1b; font-size: 16px; margin: 0;">
                                        Akun Anda telah <strong>diblokir secara permanen</strong> karena melanggar ketentuan layanan.
                                    </p>
                                </div>
                                
                                <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                    Dampak pemblokiran:
                                </p>
                                <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; padding-left: 20px;">
                                    <li>Anda tidak dapat mengakses dashboard</li>
                                    <li>Semua widget chatbot dinonaktifkan</li>
                                    <li>Data akun tetap tersimpan</li>
                                </ul>
                            @else
                                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px 20px; margin: 24px 0; border-radius: 8px;">
                                    <p style="color: #92400e; font-size: 16px; margin: 0;">
                                        Akun Anda telah <strong>ditangguhkan sementara</strong>.
                                    </p>
                                </div>
                                
                                @if($reason)
                                    <div style="background-color: #f9fafb; border-radius: 8px; padding: 16px; margin: 24px 0;">
                                        <p style="color: #6b7280; font-size: 14px; margin: 0 0 8px;">Alasan:</p>
                                        <p style="color: #374151; font-size: 16px; margin: 0;">{{ $reason }}</p>
                                    </div>
                                @endif
                                
                                <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                    Selama penangguhan:
                                </p>
                                <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; padding-left: 20px;">
                                    <li>Anda tidak dapat mengakses dashboard</li>
                                    <li>Widget chatbot dinonaktifkan sementara</li>
                                    <li>Akun dapat diaktifkan kembali setelah masalah diselesaikan</li>
                                </ul>
                            @endif
                            
                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 24px 0;">
                                Jika Anda merasa ini adalah kesalahan, silakan hubungi tim support kami.
                            </p>
                            
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="mailto:support@cekat.ai" style="display: inline-block; background-color: #374151; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Hubungi Support
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
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
