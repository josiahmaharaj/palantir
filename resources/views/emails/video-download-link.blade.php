<x-emails.layout heading="Your video is ready" :preheader="$videoLog->title.' is ready to download until '.$expiresAt.'.'">
    <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#3f3f46;">
        @if ($recipientName !== 'User')
            Hello {{ $recipientName }},
        @endif
        <strong style="color:#09090b; overflow-wrap:anywhere; word-break:break-word;">{{ $videoLog->title }}</strong> has been delivered for {{ $videoLog->broadcaster }}.
    </p>

    <x-emails.details :rows="[
        'Broadcaster' => $videoLog->broadcaster,
        'Show date' => $videoLog->due_date->format('F j, Y'),
        'Reference' => $videoLog->log_id,
        'File size' => $fileSize,
        'Available until' => $expiresAt,
    ]" />

    <x-emails.button :url="$downloadLink">Get your video</x-emails.button>

    <p style="margin:20px 0 0; font-size:13px; line-height:1.6; color:#71717a;">
        If the button doesn't work, paste this into your browser:<br>
        <a href="{{ $downloadLink }}" style="color:#b45309; word-break:break-all;">{{ $downloadLink }}</a>
    </p>
</x-emails.layout>
