<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'video_id',
        'link',
        'expired_at',
        'downloaded_at',
        'ip_address',
        'user_agent',
        'shortcode',
        'recipients',
        'email_sent_at',
        'file_uploaded_at',
    ];
}
