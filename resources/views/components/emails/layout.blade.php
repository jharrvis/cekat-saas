{{-- Professional email shell: solid ink header (no gradients, no glyphs),
     single accent by typography + spacing, table-based for client support. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? 'Cekat' }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#f4f4f5;padding:36px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e4e4e7;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#18181b;padding:26px 36px;">
                            <div
                                style="font-size:16px;font-weight:700;letter-spacing:5px;color:#ffffff;text-transform:uppercase;line-height:1;">
                                Cekat
                            </div>
                            @isset($category)
                                <div
                                    style="font-size:10px;font-weight:600;letter-spacing:2.4px;color:#a1a1aa;text-transform:uppercase;margin-top:9px;line-height:1;">
                                    {{ $category }}
                                </div>
                            @endisset
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 36px 30px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #e4e4e7;padding:20px 36px;">
                            <p
                                style="margin:0;font-size:12px;line-height:1.7;color:#a1a1aa;text-align:center;">
                                Anda menerima email ini karena ada aktivitas pada akun Cekat Anda.<br>
                                &copy; {{ date('Y') }} Cekat &middot; AI Customer Service Platform
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
