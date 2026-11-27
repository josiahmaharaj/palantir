<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Your file is ready</title>
</head>
<body style="background:#f6f9fc; margin:0; padding:32px 12px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" align="center" style="max-width:600px; width:100%;">
    <tr>
      <td style="background:#0f172a; border-radius:14px 14px 0 0; padding:24px 32px; color:#e2e8f0; font-size:18px; font-weight:600;">
        {{ $brandName }}
      </td>
    </tr>
    <tr>
      <td style="background:#ffffff; padding:32px; border:1px solid #eef2f7; border-top:0;">
        <p style="margin:0 0 12px; font-size:18px; font-weight:600; color:#0f172a;">Your file is ready</p>
        <p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#334155;">
          <strong>{{ $title }}</strong> ({{ $fileSize }}) has been shared with you.
        </p>
        <p style="margin:0 0 24px;">
          <a href="{{ $downloadUrl }}" target="_blank"
             style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:600; padding:12px 28px; border-radius:8px;">
            Download file
          </a>
        </p>
        <p style="margin:0 0 8px; font-size:13px; color:#64748b;">Or paste this link into your browser:</p>
        <p style="margin:0 0 20px; font-size:13px; word-break:break-all;">
          <a href="{{ $downloadUrl }}" style="color:#2563eb;">{{ $downloadUrl }}</a>
        </p>
        <p style="margin:0; font-size:13px; color:#64748b;">This link expires on <strong>{{ $expiresAt }}</strong>.</p>
      </td>
    </tr>
  </table>
</body>
</html>
