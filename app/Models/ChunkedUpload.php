<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChunkedUpload extends Model
{
    protected $fillable = [
        'upload_id',
        'original_name',
        'mime_type',
        'total_size',
        'total_chunks',
        'received_chunks',
        'temp_path',
        'final_path',
        'status',
        'user_id',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
