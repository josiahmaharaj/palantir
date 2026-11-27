<?php

namespace App\Filament\Pages;

use App\Models\FileUpload;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class UploadFiles extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Chunk Upload';

    protected static ?string $title = 'Chunk Upload';

    protected static string $view = 'filament.pages.upload-files';

    public string $uploadTitle = '';

    public bool $isUploading = false;

    public bool $isComplete = false;

    public ?int $fileUploadId = null;

    public function saveUpload(string $filePath, string $originalName, int $fileSize, string $mimeType): void
    {
        $fileUpload = FileUpload::create([
            'title' => $this->uploadTitle ?: $originalName,
            'original_filename' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => 'completed',
            'user_id' => auth()->id(),
        ]);

        $absolutePath = Storage::disk('local')->path($filePath);

        $fileUpload->addMedia($absolutePath)
            ->usingFileName($originalName)
            ->toMediaCollection('files');

        $this->fileUploadId = $fileUpload->id;
        $this->isComplete = true;
        $this->isUploading = false;
    }

    public function resetUpload(): void
    {
        $this->uploadTitle = '';
        $this->isUploading = false;
        $this->isComplete = false;
        $this->fileUploadId = null;
    }
}
