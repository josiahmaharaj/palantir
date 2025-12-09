<?php

namespace App\Services;

use App\Models\ChunkedUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkMergeService
{
    /**
     * @return array{path:string,size:int}
     */
    public function finalize(ChunkedUpload $upload): array
    {
        $disk = Storage::disk('local');
        $tempPath = $upload->temp_path;
        $absoluteTemp = $disk->path($tempPath);

        if (! $disk->exists($tempPath)) {
            abort(410, 'Upload no longer available.');
        }

        $targetDirectory = trim(config('upload.storage_path'), '/');
        if (! $disk->exists($targetDirectory)) {
            $disk->makeDirectory($targetDirectory);
        }

        $extension = pathinfo($upload->original_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($upload->original_name, PATHINFO_FILENAME);
        $uniqueName = Str::slug($baseName ?: 'upload').'-'.Str::ulid();
        $safeName = $uniqueName.($extension ? '.'.strtolower($extension) : '');
        $finalPath = $targetDirectory.'/'.$safeName;

        $disk->move($tempPath, $finalPath);

        $upload->final_path = $finalPath;
        $upload->status = 'complete';
        $upload->save();

        $finalSize = filesize($disk->path($finalPath));
        if ($finalSize !== (int) $upload->total_size) {
            $upload->status = 'failed';
            $upload->save();
            abort(422, 'Merged file failed integrity check.');
        }

        return [
            'path' => $finalPath,
            'size' => $finalSize,
        ];
    }
}
