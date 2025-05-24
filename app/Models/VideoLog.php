<?php

namespace App\Models;

use App\Broadcaster;
use Illuminate\Database\Eloquent\Model;

class VideoLog extends Model
{
    protected $fillable = [
        'id',
        'log_id',
        'title',
        'broadcaster',
        'due_date',
        'file',
        'status',
        'related_log_id',
    ];

    public function broadcaster()
    {
        return $this->belongsTo(Broadcaster::class, 'broadcaster');
    }
}
