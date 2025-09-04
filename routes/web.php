<?php

use App\Http\Controllers\VideoLogController;
use Illuminate\Support\Facades\Route;

// Video download routes
Route::get('/video/download/{token}', [VideoLogController::class, 'download'])->name('video.download');
