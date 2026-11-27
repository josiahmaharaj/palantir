<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadLink extends Model
{
    protected $fillable = [
        'contact_id',
        'email',
        'title',
        'description',
        'media_id',
        'link',
        'status',
        'expires_at',
        'opened_at',
        'downloaded_at',
        'ip_address',
        'user_agent',
        'number_clicks',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

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
        return route('download-link.show', ['token' => $this->link]);
    }
}
