<?php

namespace App\Livewire;

use App\Mail\FileDownloadLink;
use App\Models\Contact;
use App\Models\DownloadLink;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FileUploadPage extends Component
{
    public string $title = '';

    public string $email = '';

    public bool $isUploading = false;

    public bool $isComplete = false;

    public ?int $fileUploadId = null;

    public function validateRecipient(): bool
    {
        $this->resetErrorBag();

        $validator = Validator::make(['email' => $this->email], ['email' => ['required', 'email:rfc', 'max:255']]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());

            return false;
        }

        return true;
    }

    public function saveUpload(string $filePath, string $originalName, int $fileSize, string $mimeType): void
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

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

        $this->sendDownloadLink($fileUpload);

        $this->fileUploadId = $fileUpload->id;
        $this->isComplete = true;
        $this->isUploading = false;
    }

    protected function sendDownloadLink(FileUpload $fileUpload): void
    {
        $media = $fileUpload->getFirstMedia('files');

        $downloadLink = DownloadLink::create([
            'contact_id' => Contact::where('email', $this->email)->value('id'),
            'email' => $this->email,
            'title' => $fileUpload->title,
            'media_id' => $media->id,
            'link' => Str::random(64),
            'status' => 'sent',
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($this->email)->send(new FileDownloadLink($downloadLink, Number::fileSize($media->size)));
        } catch (\Throwable $e) {
            report($e);
            $downloadLink->update(['status' => 'error']);
            $this->addError('email', 'The file was uploaded but the email could not be sent.');
        }
    }

    public function resetUpload(): void
    {
        $this->title = '';
        $this->email = '';
        $this->resetErrorBag();
        $this->isUploading = false;
        $this->isComplete = false;
        $this->fileUploadId = null;
    }

    public function render()
    {
        return view('livewire.file-upload-page');
    }
}
