<x-emails.layout heading="Your file is ready" :preheader="$title.' is ready to download until '.$expiresAt.'.'">
    <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#3f3f46;">
        <strong style="color:#09090b; overflow-wrap:anywhere; word-break:break-word;">{{ $title }}</strong> has been shared with you.
    </p>

    <x-emails.details :rows="['File size' => $fileSize, 'Available until' => $expiresAt]" />

    <x-emails.button :url="$downloadUrl">Get your file</x-emails.button>

    <p style="margin:20px 0 0; font-size:13px; line-height:1.6; color:#71717a;">
        If the button doesn't work, paste this into your browser:<br>
        <a href="{{ $downloadUrl }}" style="color:#b45309; word-break:break-all;">{{ $downloadUrl }}</a>
    </p>
</x-emails.layout>
