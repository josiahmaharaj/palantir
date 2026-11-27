@props(['heading', 'preheader' => null])
@php($font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif")
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f5; -webkit-text-size-adjust:100%;">
    @if ($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px; color:#f4f4f5;">{{ $preheader }}</div>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:560px; font-family:{{ $font }};">
                    <tr>
                        <td style="background:#09090b; border-radius:16px 16px 0 0; border-bottom:3px solid #f59e0b; padding:24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-right:12px;">
                                        <div style="width:14px; height:14px; border:4px solid #f59e0b; border-radius:50%; font-size:0; line-height:0;">&nbsp;</div>
                                    </td>
                                    <td style="font-family:{{ $font }}; font-size:17px; font-weight:600; color:#fafafa;">{{ config('app.name') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff; padding:32px 24px;">
                            <h1 style="margin:0 0 12px; font-size:24px; line-height:1.3; font-weight:600; letter-spacing:-0.01em; color:#09090b;">{{ $heading }}</h1>
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fafafa; border-top:1px solid #e4e4e7; border-radius:0 0 16px 16px; padding:20px 24px; font-size:12px; line-height:1.7; color:#71717a;">
                            @if (config('mail.support_address'))
                                Need help? Write to <a href="mailto:{{ config('mail.support_address') }}" style="color:#b45309;">{{ config('mail.support_address') }}</a>.<br>
                            @endif
                            Sent automatically by {{ config('app.name') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
