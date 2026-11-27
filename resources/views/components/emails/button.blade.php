@props(['url'])
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" bgcolor="#f59e0b" style="border-radius:10px;">
            <a href="{{ $url }}" target="_blank" style="display:block; padding:15px 24px; font-size:16px; font-weight:600; color:#09090b; text-decoration:none;">{{ $slot }}</a>
        </td>
    </tr>
</table>
