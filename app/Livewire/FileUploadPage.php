<?php

namespace App\Livewire;

use App\Models\FileUpload;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FileUploadPage extends Component
{
    public string $title = '';

    public bool $isUploading = false;

    public bool $isComplete = false;

    public ?int $fileUploadId = null;

    public function saveUpload(string $filePath, string $originalName, int $fileSize, string $mimeType): void
    {
        $fileUpload = FileUpload::create([
            'title' => $this->title ?: $originalName,
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
        $this->title = '';
        $this->isUploading = false;
        $this->isComplete = false;
        $this->fileUploadId = null;
    }

    public function render()
    {
        return view('livewire.file-upload-page');
    }
}
