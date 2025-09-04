<?php

namespace App\Models;

use App\Broadcaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = ['email', 'broadcast'];

    protected $casts = [
        'broadcast' => Broadcaster::class,
    ];
}
