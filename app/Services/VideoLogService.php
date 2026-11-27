<?php

namespace App\Services;

use App\Mail\VideoDownloadLink;
use App\Models\Contact;
use App\Models\Link;
use App\Models\VideoLog;
use App\Models\VideoLogAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class VideoLogService
{
    /**
     * Create a download link for each contact of the log's broadcaster and email it.
     *
     * @return array{sent: int, failed: int}
     */
    public static function sendDownloadLink(VideoLog $videoLog): array
    {
        $media = $videoLog->videoMedia();
        $result = ['sent' => 0, 'failed' => 0];

        if (! $media) {
            return $result;
        }

        $videoLog->auditLogs()->create([
            'event' => VideoLogAuditLog::VideoUploaded,
            'message' => 'Video uploaded: '.$media->file_name.' ('.self::getFileSize($media).')',
        ]);

        $contacts = Contact::where('broadcast', $videoLog->broadcaster)->get();
        if ($contacts->isEmpty()) {
            $videoLog->auditLogs()->create([
                'event' => VideoLogAuditLog::NoContacts,
                'message' => 'No contacts found for '.$videoLog->broadcaster.'; no emails were sent.',
            ]);

            return $result;
        }

        $fileSize = self::getFileSize($media);
        $downloadLinks = app(DownloadLinkService::class);

        foreach ($contacts as $contact) {
            $downloadLink = $downloadLinks->create($media, $contact->email, $videoLog->title, $contact->id);
            $expiresAt = $downloadLink->expires_at;

            try {
                $downloadLinks->send($downloadLink, new VideoDownloadLink(
                    config('app.name'),
                    $videoLog,
                    $downloadLink->getDownloadUrl(),
                    $expiresAt->format('F j, Y \\a\\t g:i A'),
                    $fileSize,
                    'User'
                ));

                $videoLog->auditLogs()->create([
                    'event' => VideoLogAuditLog::EmailSent,
                    'email' => $contact->email,
                    'message' => 'Download link emailed; expires '.$expiresAt->format('M j, Y g:i A'),
                ]);
                $result['sent']++;
            } catch (Throwable $e) {
                report($e);
                $videoLog->auditLogs()->create([
                    'event' => VideoLogAuditLog::EmailFailed,
                    'email' => $contact->email,
                    'message' => $e->getMessage(),
                ]);
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Get human readable file size
     */
    private static function getFileSize(Media $media): string
    {
        $bytes = $media->size;
        if (! $bytes) {
            return 'Unknown size';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    public function download(Request $request, $token)
    {
        $link = Link::where('link', $token)
            ->where('expired_at', '>', now())
            ->first();

        if (! $link) {
            abort(404, 'Download link has expired or is invalid.');
        }

        $videoLog = VideoLog::find($link->video_id);
        $media = $videoLog?->videoMedia();
        if (! $videoLog || ! $media) {
            abort(404, 'Video file not found.');
        }

        // Update download timestamp and IP address
        $link->update([
            'downloaded_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'mp4';
        $downloadName = $videoLog->title.'.'.$extension;

        return response()->download($media->getPath(), $downloadName);
    }

    public static function handleFileUploaded($state, $livewire)
    {
        $uploaded = $state instanceof TemporaryUploadedFile
                        ? $state
                        : (is_array($state) && $state[0] instanceof TemporaryUploadedFile ? $state[0] : null);

        if (! $uploaded) {
            return;
        }

        // Build a safe filename (original name, slugified, keep extension)
        $originalName = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploaded->getClientOriginalExtension();
        $safeName = Str::slug($originalName).'.'.$extension;

        $path = $uploaded->storeAs('', $safeName, 'local');

        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : ($livewire->record ?? null);
        if ($record) {
            $record->replaceVideoFromPath($path, $safeName);
        }

        return $path;
    }
}
