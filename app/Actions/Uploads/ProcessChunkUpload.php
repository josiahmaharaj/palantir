<?php

namespace App\Actions\Uploads;

use App\Data\UploadChunkData;
use App\Models\ChunkedUpload;
use App\Services\ChunkMergeService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessChunkUpload
{
    public function __construct(private ChunkMergeService $mergeService) {}

    /**
     * @return array{status:string,next_chunk:int,upload_id:string}|array{status:string,file_path:string,original_name:string,size:int,mime_type:string}
     */
    public function handle(UploadChunkData $data, string $chunkBinary, ?int $userId = null): array
    {
        $this->cleanupAbandoned();

        $maxSize = config('upload.max_upload_size');
        if ($data->totalSize > $maxSize) {
            abort(413, 'File exceeds maximum allowed size.');
        }

        $upload = ChunkedUpload::firstOrNew(['upload_id' => $data->uploadId]);

        if (! $upload->exists) {
            $upload->fill([
                'upload_id' => $data->uploadId,
                'original_name' => $this->sanitizeName($data->originalName),
                'total_size' => $data->totalSize,
                'total_chunks' => $data->totalChunks,
                'received_chunks' => 0,
                'temp_path' => $this->buildTempPath($data->uploadId, $data->originalName),
                'status' => 'pending',
                'user_id' => $userId,
            ]);
        } else {
            if ($upload->total_size !== $data->totalSize || $upload->total_chunks !== $data->totalChunks) {
                abort(409, 'Upload metadata mismatch.');
            }
        }

        if ($upload->received_chunks > $data->chunkIndex) {
            $upload->last_activity_at = now();
            $upload->save();

            return [
                'status' => 'partial',
                'next_chunk' => $upload->received_chunks,
                'upload_id' => $upload->upload_id,
            ];
        }

        if ($data->chunkIndex !== $upload->received_chunks) {
            abort(409, 'Chunks must be uploaded sequentially.');
        }

        $detectedMime = $this->detectMime($chunkBinary);
        $allowed = config('upload.allowed_mimes');
        $allowedExtensions = config('upload.allowed_extensions');
        $extension = strtolower((string) pathinfo($data->originalName, PATHINFO_EXTENSION));
        $isAllowedMime = in_array($detectedMime, $allowed, true);
        $isAllowedByExtension = $detectedMime === 'application/octet-stream' && in_array($extension, $allowedExtensions, true);
        if (! $isAllowedMime && ! $isAllowedByExtension) {
            abort(415, 'This file type is not allowed.');
        }

        $rangeLength = ($data->contentEnd - $data->contentStart) + 1;
        if (strlen($chunkBinary) !== $rangeLength) {
            abort(422, 'Chunk size does not match Content-Range.');
        }

        $upload->mime_type ??= $detectedMime;
        $this->persistChunk($upload->temp_path, $chunkBinary, $data->chunkIndex === 0);

        $upload->received_chunks = $data->chunkIndex + 1;
        $upload->status = $data->chunkIndex + 1 >= $data->totalChunks ? 'merging' : 'in_progress';
        $upload->last_activity_at = now();
        $upload->save();

        if ($data->chunkIndex + 1 >= $data->totalChunks) {
            $result = $this->mergeService->finalize($upload);

            return [
                'status' => 'complete',
                'file_path' => $result['path'],
                'original_name' => $upload->original_name,
                'size' => $result['size'],
                'mime_type' => $upload->mime_type ?? $detectedMime,
            ];
        }

        return [
            'status' => 'partial',
            'next_chunk' => $upload->received_chunks,
            'upload_id' => $upload->upload_id,
        ];
    }

    private function persistChunk(string $tempPath, string $chunkBinary, bool $firstChunk): void
    {
        $disk = Storage::disk('local');
        $directory = dirname($tempPath);
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $absolutePath = $disk->path($tempPath);

        if ($firstChunk && file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        file_put_contents($absolutePath, $chunkBinary, FILE_APPEND);
    }

    private function sanitizeName(string $original): string
    {
        $name = pathinfo($original, PATHINFO_FILENAME);
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $safeBase = Str::slug($name) ?: 'upload';
        $safeExtension = $extension ? '.'.strtolower($extension) : '';

        return $safeBase.$safeExtension;
    }

    private function buildTempPath(string $uploadId, string $originalName): string
    {
        $directory = trim(config('upload.temp_path'), '/');
        $safeName = $this->sanitizeName($originalName);

        return $directory.'/'.$uploadId.'-'.$safeName.'.part';
    }

    private function detectMime(string $chunkBinary): string
    {
        $resource = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($resource, $chunkBinary);
        finfo_close($resource);

        return (string) $mime;
    }

    private function cleanupAbandoned(): void
    {
        $expiresAt = CarbonImmutable::now()->subMinutes((int) config('upload.cleanup_after_minutes'));

        $expired = ChunkedUpload::query()
            ->where('status', '!=', 'complete')
            ->where(function ($query) use ($expiresAt) {
                $query->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $expiresAt);
            })
            ->get();

        foreach ($expired as $upload) {
            if ($upload->temp_path && Storage::disk('local')->exists($upload->temp_path)) {
                Storage::disk('local')->delete($upload->temp_path);
            }

            $upload->delete();
        }
    }
}
