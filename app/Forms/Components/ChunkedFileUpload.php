<?php

namespace App\Forms\Components;

class ChunkedFileUpload extends StreamingFileUpload
{
    protected string $view = 'filament.forms.components.chunked-file-upload';
}
