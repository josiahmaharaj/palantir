<?php

namespace App\Services;

use App\Mail\VideoDownloadLink;
use App\Models\Contact;
use App\Models\Link;
use App\Models\VideoLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VideoLogService
{
    public static function sendDownloadLink(VideoLog $videoLog)
    {
        // Generate a temporary encrypted link
        $token = Str::random(64);
        $expiresAt = now()->addMinute(4); // Link expires in 7 days

        // Create the link record
        $link = Link::create([
            'video_id' => $videoLog->id,
            'link' => $token,
            'expired_at' => $expiresAt,
            'shortcode' => Str::random(8),
            'recipients' => 'contacts',
        ]);

        // Get all contacts
        $contacts = Contact::where('broadcast', $videoLog->broadcaster)->get();

        if ($contacts->isEmpty()) {
            return false;
        }

        // Generate the download URL
        $downloadUrl = route('video.download', ['token' => $token]);

        // Get file size for email
        $media = $videoLog->videoMedia();
        if (! $media) {
            return false;
        }

        $fileSize = self::getFileSize($media);

        // Send email to each contact
        foreach ($contacts as $contact) {
            Mail::to($contact->email)->queue(new VideoDownloadLink(
                config('app.name'),
                $videoLog,
                $downloadUrl,
                $expiresAt->format('F j, Y \a\t g:i A'),
                $fileSize,
                'User' // Contact model doesn't have name field
            ));
        }

        // Update the link record with email sent timestamp
        $link->update(['email_sent_at' => now()]);

        return true;
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
