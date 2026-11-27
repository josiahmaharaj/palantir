<?php

namespace App\Http\Controllers;

use App\Models\VideoLog;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoLogVideoController extends Controller
{
    /**
     * Let a signed in user fetch the video attached to a log.
     */
    public function __invoke(VideoLog $videoLog): BinaryFileResponse
    {
        $media = $videoLog->videoMedia();

        abort_unless($media && file_exists($media->getPath()), 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
