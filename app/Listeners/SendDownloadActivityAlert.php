<?php

namespace App\Listeners;

use App\Events\DownloadLinkOpened;
use App\Events\FileDownloaded;
use App\Mail\DownloadLinkActivity;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDownloadActivityAlert
{
    public function handle(DownloadLinkOpened|FileDownloaded $event): void
    {
        $notifyAddress = config('mail.notify_address');

        if (! $notifyAddress) {
            return;
        }

        $activity = $event instanceof DownloadLinkOpened ? DownloadLinkActivity::Opened : DownloadLinkActivity::Downloaded;

        try {
            Mail::to($notifyAddress)->send(new DownloadLinkActivity($event->downloadLink, $activity));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
