<?php

use App\Http\Controllers\VideoLogController;
use Illuminate\Support\Facades\Route;

// Video download routes
// Route::post('/video-logs/{videoLog}/send', [VideoLogController::class, 'sendDownloadLink'])->name('video.send');
Route::get('/video/download/{token}', [VideoLogController::class, 'download'])->name('video.download');

// Route::get('/', function () {
// dd([
//     //php values and file size max
//     'php' => [
//         'values' => [
//             'upload_max_filesize' => ini_get('upload_max_filesize'),
//             'post_max_size' => ini_get('post_max_size'),
//         ],
//     ],
//     'file' => [
//         'size' => ini_get('upload_max_filesize'),
//     ],
// ]);

// });
