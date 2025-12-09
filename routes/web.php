<?php

use App\Http\Controllers\VideoLogController;
use Illuminate\Support\Facades\Route;

Route::post('/api/upload/chunk', \App\Http\Controllers\Api\ChunkUploadController::class)
    ->middleware(['web', 'throttle:upload-chunks'])
    ->name('api.upload.chunk');

Route::get('/video/download/{token}', [VideoLogController::class, 'download'])->name('video.download');
