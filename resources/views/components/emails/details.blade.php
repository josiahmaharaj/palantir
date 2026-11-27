@props(['rows'])
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px; border-top:1px solid #e4e4e7;">
    @foreach ($rows as $label => $value)
        <tr>
            <td valign="top" style="padding:11px 16px 11px 0; border-bottom:1px solid #e4e4e7; font-size:14px; color:#71717a; white-space:nowrap;">{{ $label }}</td>
            <td align="right" style="padding:11px 0; border-bottom:1px solid #e4e4e7; font-size:14px; font-weight:600; color:#18181b; word-break:break-word; overflow-wrap:anywhere;">{{ $value }}</td>
        </tr>
    @endforeach
</table>
