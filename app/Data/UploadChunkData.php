<?php

namespace App\Data;

use Illuminate\Http\Request;

class UploadChunkData
{
    public function __construct(
        public readonly string $uploadId,
        public readonly int $chunkIndex,
        public readonly int $totalChunks,
        public readonly int $contentStart,
        public readonly int $contentEnd,
        public readonly int $totalSize,
        public readonly string $originalName,
        public readonly ?string $mimeType,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $contentRange = $request->header('Content-Range');
        if (! $contentRange || ! preg_match('/bytes (\d+)-(\d+)\/(\d+)/', $contentRange, $matches)) {
            abort(422, 'Invalid or missing Content-Range header.');
        }

        $uploadId = (string) $request->header('X-Upload-Id');
        $chunkIndex = (int) $request->header('X-Chunk-Index');
        $totalChunks = (int) $request->header('X-Total-Chunks');
        $originalName = urldecode((string) $request->header('X-Original-Name'));
        $mimeType = $request->header('X-Mime-Type');

        if ($uploadId === '' || $originalName === '' || $totalChunks < 1) {
            abort(422, 'Missing upload metadata.');
        }

        if ($chunkIndex < 0 || $totalChunks < 1 || (int) $matches[2] < (int) $matches[1] || (int) $matches[3] < 1) {
            abort(422, 'Invalid chunk data.');
        }

        return new self(
            uploadId: $uploadId,
            chunkIndex: $chunkIndex,
            totalChunks: $totalChunks,
            contentStart: (int) $matches[1],
            contentEnd: (int) $matches[2],
            totalSize: (int) $matches[3],
            originalName: $originalName,
            mimeType: $mimeType ? strtolower($mimeType) : null,
        );
    }
}
