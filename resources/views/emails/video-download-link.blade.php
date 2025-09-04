<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="x-apple-disable-message-reformatting">
  <title>Your file is ready</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style>
    /* Resets & helpers */
    html, body { margin:0 !important; padding:0 !important; height:100% !important; width:100% !important; }
    * { -ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; }
    table, td { mso-table-lspace:0pt !important; mso-table-rspace:0pt !important; border-collapse:collapse !important; }
    img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
    a[x-apple-data-detectors], .unstyle-auto-detected-links a { color:inherit !important; text-decoration:none !important; }
    /* Mobile tweaks */
    @media screen and (max-width:600px){
      .container { width:100% !important; }
      .sm-px { padding-left:24px !important; padding-right:24px !important; }
      .stack { display:block !important; width:100% !important; }
    }
  </style>
</head>
<body style="background:#f6f9fc; margin:0; padding:0;">
  <!-- Preheader (hidden preview text) -->
  <div style="display:none; font-size:1px; color:#f6f9fc; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Your file is ready to download. The link expires in 7 days.
  </div>

  <center role="article" aria-roledescription="email" lang="en" style="width:100%; background:#f6f9fc;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
      <tr>
        <td align="center" style="padding:32px 12px;">
          <!-- Container -->
          <table role="presentation" class="container" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px; width:100%;">
            <!-- Header -->
            <tr>
              <td style="background:#0f172a; border-radius:14px 14px 0 0; padding:28px 32px;">
                <table role="presentation" width="100%">
                  <tr>
                    <td align="left" style="font:600 18px/1.3 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#e2e8f0;">
                     <img src="{{ asset('images/logo.svg') }}" alt="{{ $brandName }}" style="height: 20px; width: auto;"> {{ $brandName }}
                    </td>
                    <td align="right" style="font:500 12px/1.3 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#94a3b8;">
                      Download Center
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="background:#ffffff; padding:32px; border:1px solid #eef2f7; border-top:0; border-bottom:0;">
                <!-- Greeting -->
                <p style="margin:0 0 12px; font:600 18px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#0f172a;">
                  Hi {{ $recipientName }},
                </p>
                <p style="margin:0 0 24px; font:400 15px/1.6 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#334155;">
                  Your file is ready to download. We’ve included the details below.
                </p>

                <!-- Details card -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb; border-radius:12px;">
                  <tr>
                    <td style="padding:20px 22px;">
                      <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                        <tr class="stack">
                          <td class="stack" style="padding:0 0 10px; font:700 16px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#0f172a;">
                            🎬 {{ $videoLog->title }}
                          </td>
                          <td class="stack" align="right" style="padding:0 0 10px; font:500 12px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#64748b;">
                            Ref: {{ $videoLog->log_id }}
                          </td>
                        </tr>
                        <tr>
                          <td colspan="2" style="padding:0; font:400 14px/1.7 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#475569;">
                            <div style="margin:0 0 6px;"><span style="color:#94a3b8;">Broadcaster:</span> <strong style="color:#0f172a;">{{ $videoLog->broadcaster }}</strong></div>
                            <div style="margin:0 0 6px;"><span style="color:#94a3b8;">Show date:</span> <strong style="color:#0f172a;">{{ $videoLog->due_date->format('F j, Y') }}</strong></div>
                            <div style="margin:0;"><span style="color:#94a3b8;">File size:</span> <strong style="color:#0f172a;">{{ $fileSize }}</strong></div>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>

                <!-- Spacer -->
                <div style="height:20px; line-height:20px;">&nbsp;</div>

                <!-- Button (bulletproof) -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:auto;">
                  <tr>
                    <td align="center">
                      <!--[if mso]>
                      <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $downloadLink }}" style="height:44px; v-text-anchor:middle; width:240px;" arcsize="12%" stroke="f" fillcolor="#2563eb">
                        <w:anchorlock/>
                        <center style="color:#ffffff; font-family:Segoe UI, Arial, sans-serif; font-size:16px; font-weight:600;">
                          Download file
                        </center>
                      </v:roundrect>
                      <![endif]-->
                      <!--[if !mso]><!-- -->
                      <a href="{{ $downloadLink }}" target="_blank"
                         style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font:600 16px/44px -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; padding:0 28px; border-radius:8px; min-width:220px; text-align:center;">
                        Download file
                      </a>
                      <!--<![endif]-->
                    </td>
                  </tr>
                </table>

                <!-- Secondary link (fallback) -->
                <p style="margin:16px 0 0; font:400 13px/1.6 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#64748b; text-align:center;">
                  If the button doesn't work, copy and paste this link into your browser:<br>
                  <a href="{{ $downloadLink }}" style="color:#2563eb; text-decoration:underline; word-break:break-all;">{{ $downloadLink }}</a>
                </p>

                <!-- Expiry notice -->
                <div style="margin:18px 0 0; padding:12px 14px; background:#f1f5f9; border:1px dashed #cbd5e1; border-radius:10px; font:500 13px/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#0f172a;">
                  ⏰ This link will expire on <strong>{{ $expiresAt }}</strong>.
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background:#ffffff; border:1px solid #eef2f7; border-top:0; border-radius:0 0 14px 14px; padding:20px 32px;">
                <p style="margin:0 0 6px; font:400 12px/1.7 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#64748b;">
                  This is an automated message. If you need support, please contact me directly at
                  <a href="mailto:{{ env('SUPPORT_EMAIL')}}" style="color:#2563eb; text-decoration:underline;">{{ env('SUPPORT_EMAIL')}}</a>.
                </p>
                <p style="margin:0; font:400 12px/1.7 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#94a3b8;">
                  © {{ date('Y') }} Palantir
                </p>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </center>
</body>
</html>
