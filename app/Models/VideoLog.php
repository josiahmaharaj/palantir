<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoLog extends Model
{
    protected $fillable = [
        'id',
        'title',
        'broadcaster',
        'file',
        'status',
        'related_log_id',
    ];
}
