<?php

namespace App\Events;

use App\Models\DownloadLink;
use Illuminate\Foundation\Events\Dispatchable;

class FileDownloaded
{
    use Dispatchable;

    public function __construct(public DownloadLink $downloadLink) {}
}
