<?php

namespace App\Models;

use App\Broadcaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VideoLog extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'id',
        'log_id',
        'title',
        'broadcaster',
        'media_id',
        'due_date',
        'status',
        'related_log_id',
    ];

    // casts
    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    public function broadcaster()
    {
        return $this->belongsTo(Broadcaster::class, 'broadcaster');
    }

    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('video-log-videos')
            ->singleFile();
    }

    public function videoMedia(): ?Media
    {
        if ($this->media_id) {
            return $this->media()->where('id', $this->media_id)->first();
        }

        return $this->getFirstMedia('video-log-videos');
    }

    public function replaceVideoFromPath(string $path, string $originalName): ?Media
    {
        $absolutePath = Storage::disk('local')->path($path);
        if (! file_exists($absolutePath)) {
            Log::error('VideoLog::replaceVideoFromPath - File does not exist', [
                'path' => $path,
                'absolute_path' => $absolutePath,
                'video_log_id' => $this->id,
            ]);

            return null;
        }

        $this->clearMediaCollection('video-log-videos');

        try {
            $media = $this->addMedia($absolutePath)
                ->usingFileName($originalName)
                ->toMediaCollection('video-log-videos');

            // Ensure the media_id is saved and linked to the VideoLog
            $this->media_id = $media->id;
            $saved = $this->save();

            if (! $saved) {
                Log::error('VideoLog::replaceVideoFromPath - Failed to save media_id', [
                    'video_log_id' => $this->id,
                    'media_id' => $media->id,
                ]);

                return null;
            }

            return $media;
        } catch (\Throwable $e) {
            Log::error('VideoLog::replaceVideoFromPath - Exception', [
                'video_log_id' => $this->id,
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
