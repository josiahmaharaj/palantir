Your video is ready

{!! $videoLog->title !!} has been delivered for {!! $videoLog->broadcaster !!}.

Show date: {{ $videoLog->due_date->format('F j, Y') }}
Reference: {{ $videoLog->log_id }}
File size: {{ $fileSize }}
Available until: {{ $expiresAt }}

Get your video:
{!! $downloadLink !!}
