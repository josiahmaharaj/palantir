<?php

namespace App\Listeners;

use App\Events\DownloadLinkOpened;
use App\Events\FileDownloaded;
use App\Models\VideoLog;
use App\Models\VideoLogAuditLog;

class RecordDeliveryInVideoLogAudit
{
    public function handle(DownloadLinkOpened|FileDownloaded $event): void
    {
        $videoLog = $event->downloadLink->media?->model;

        if (! $videoLog instanceof VideoLog) {
            return;
        }

        $isOpen = $event instanceof DownloadLinkOpened;

        $videoLog->auditLogs()->create([
            'event' => $isOpen ? VideoLogAuditLog::LinkOpened : VideoLogAuditLog::FileDownloaded,
            'email' => $event->downloadLink->email,
            'message' => $isOpen ? 'Download link opened' : 'File downloaded',
        ]);
    }
}
