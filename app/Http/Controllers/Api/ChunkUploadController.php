<?php

namespace App\Http\Controllers\Api;

use App\Actions\Uploads\ProcessChunkUpload;
use App\Data\UploadChunkData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChunkUploadController extends Controller
{
    public function __invoke(Request $request, ProcessChunkUpload $processor)
    {
        $data = UploadChunkData::fromRequest($request);
        $chunkBinary = $request->getContent();

        if ($chunkBinary === '') {
            abort(422, 'Chunk payload is required.');
        }

        $result = $processor->handle(
            data: $data,
            chunkBinary: $chunkBinary,
            userId: $request->user()?->getAuthIdentifier()
        );

        return response()->json($result);
    }
}
