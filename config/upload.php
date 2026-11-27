<?php

return [
    'storage_path' => env('UPLOAD_STORAGE_PATH', 'videos'),
    'temp_path' => env('UPLOAD_TEMP_PATH', 'uploads/chunks'),
    'max_upload_size' => (int) env('UPLOAD_MAX_SIZE', 2 * 1024 * 1024 * 1024),
    'allowed_mimes' => array_filter([
        'video/mp4',
        'video/quicktime',
        'video/x-matroska',
        'video/x-msvideo',
        'video/mpeg',
    ]),
    'allowed_extensions' => [
        'mp4',
        'mov',
        'mkv',
        'avi',
        'mpeg',
        'mpg',
    ],
    'cleanup_after_minutes' => (int) env('UPLOAD_CLEANUP_AFTER_MINUTES', 120),
];
