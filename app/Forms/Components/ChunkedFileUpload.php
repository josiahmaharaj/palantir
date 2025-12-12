<?php

namespace App\Forms\Components;

use App\Models\VideoLog;

class ChunkedFileUpload extends StreamingFileUpload
{
    protected string $view = 'filament.forms.components.chunked-file-upload';

    public function getDownloadUrl(): ?string
    {
        $livewire = $this->getLivewire();
        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : ($livewire->record ?? null);

        if (! $record instanceof VideoLog) {
            return null;
        }

        $media = $record->videoMedia();

        return $media?->getUrl();
    }
}
