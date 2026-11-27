<?php

use App\Http\Controllers\Api\ChunkUploadController;
use App\Http\Controllers\DownloadLinkController;
use App\Http\Controllers\VideoLogController;
use App\Http\Controllers\VideoLogVideoController;
use App\Livewire\FileUploadPage;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::post('/api/upload/chunk', ChunkUploadController::class)
    ->withoutMiddleware([ThrottleRequests::class])
    ->middleware('auth')
    ->name('api.upload.chunk');

Route::get('/video/download/{token}', [VideoLogController::class, 'download'])->name('video.download');

Route::get('/download/{token}', [DownloadLinkController::class, 'show'])->name('download-link.show');
Route::post('/download/{token}/file', [DownloadLinkController::class, 'download'])->name('download-link.file');

Route::get('/upload', FileUploadPage::class)->middleware('auth')->name('upload');

Route::get('/video-logs/{videoLog}/video', VideoLogVideoController::class)->middleware('auth')->name('video-logs.video');
