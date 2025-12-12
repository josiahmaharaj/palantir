<?php

use App\Http\Controllers\VideoLogController;
use App\Livewire\FileUploadPage;
use Illuminate\Support\Facades\Route;

Route::post('/api/upload/chunk', \App\Http\Controllers\Api\ChunkUploadController::class)
    ->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class])
    ->name('api.upload.chunk');

Route::get('/video/download/{token}', [VideoLogController::class, 'download'])->name('video.download');

Route::get('/upload', FileUploadPage::class)->name('upload');
