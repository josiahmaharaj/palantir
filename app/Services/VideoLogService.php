<?php

namespace App\Services;

use App\Mail\VideoDownloadLink;
use App\Models\Contact;
use App\Models\VideoLog;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VideoLogService
{
    public static function sendDownloadLink(VideoLog $videoLog)
    {
        // Generate a temporary encrypted link
        $token = Str::random(64);
        $expiresAt = now()->addDays(7); // Link expires in 7 days
        
        // Create the link record
        $link = Link::create([
            'video_id' => $videoLog->id,
            'link' => $token,
            'expired_at' => $expiresAt,
            'shortcode' => Str::random(8),
            'recipients' => 'contacts',
        ]);
        
        // Get all contacts
        $contacts = Contact::all();
        
        // Generate the download URL
        $downloadUrl = route('video.download', ['token' => $token]);

        // Get file size for email
        $fileSize = self::getFileSize($videoLog->file);
        
        // Send email to each contact
        foreach ($contacts as $contact) {
            Mail::to($contact->email)->send(new VideoDownloadLink(
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
    private static function getFileSize($filePath)
    {
        if (!$filePath) {
            return 'Unknown size';
        }
        
        $fullPath = storage_path('app/public/' . $filePath);
        
        if (!file_exists($fullPath)) {
            return 'File not found';
        }
        
        $bytes = filesize($fullPath);
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public function download(Request $request, $token)
    {
        $link = Link::where('link', $token)
            ->where('expired_at', '>', now())
            ->first();
            
        if (!$link) {
            abort(404, 'Download link has expired or is invalid.');
        }
        
        $videoLog = VideoLog::find($link->video_id);
        if (!$videoLog || !$videoLog->file) {
            abort(404, 'Video file not found.');
        }
        
        // Update download timestamp and IP address
        $link->update([
            'downloaded_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Return the file for download
        return response()->download(
            storage_path('app/public/' . $videoLog->file),
            $videoLog->title . '.mp4'
        );
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
                    $extension    = $uploaded->getClientOriginalExtension();
                    $safeName     = Str::slug($originalName) . '.' . $extension;

                    // Store the file with its original/safe name in "videos/"
                    $path = $uploaded->storeAs('', $safeName, 'public');

                    // Persist to DB immediately
                    $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : ($livewire->record ?? null);
                    if ($record) {
                        if (filled($record->file)) {
                            Storage::disk('public')->delete($record->file);
                        }
                        $record->forceFill(['file' => $path])->save();
                    }

                    return $path;
    }

    public static function deleteFile($filePath)
    {
        dd($filePath);
        Storage::disk('public')->delete($filePath);
    }
}
