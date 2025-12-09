<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class StreamingFileUpload extends Field
{
    protected string $view = 'filament.forms.components.streaming-file-upload';

    protected int $chunkSize = 10 * 1024 * 1024;

    protected ?string $uploadEndpoint = null;

    public function chunkSize(int $bytes): self
    {
        $this->chunkSize = $bytes;

        return $this;
    }

    public function uploadEndpoint(string $endpoint): self
    {
        $this->uploadEndpoint = $endpoint;

        return $this;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function getUploadEndpoint(): string
    {
        return $this->uploadEndpoint ?? route('api.upload.chunk');
    }
}
