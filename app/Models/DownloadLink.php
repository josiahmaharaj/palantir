<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadLink extends Model
{
    protected $fillable = [
        'contact_id',
        'title',
        'description',
        'media_id',
        'link',
        'status',
        'expires_at',
        'downloaded_at',
        'ip_address',
        'user_agent',
        'number_clicks',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function getDownloadUrl(): string
    {
        return $this->link;
    }
}
