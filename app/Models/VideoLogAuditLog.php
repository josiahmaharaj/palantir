<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoLogAuditLog extends Model
{
    public const VideoUploaded = 'video_uploaded';

    public const EmailSent = 'email_sent';

    public const EmailFailed = 'email_failed';

    public const NoContacts = 'no_contacts';

    public const LinkOpened = 'link_opened';

    public const FileDownloaded = 'file_downloaded';

    protected $fillable = [
        'video_log_id',
        'event',
        'email',
        'message',
    ];

    public function videoLog(): BelongsTo
    {
        return $this->belongsTo(VideoLog::class);
    }
}
