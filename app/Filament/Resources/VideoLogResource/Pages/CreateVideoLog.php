<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource;
use App\Services\VideoLogService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateVideoLog extends CreateRecord
{
    protected static string $resource = VideoLogResource::class;

    protected $listeners = [
        'streaming-upload-completed' => 'handleStreamingUploadCompleted',
    ];

    public array $uploadMeta = [];

    public function mount(): void
    {
        $dueDate = request('date') ?? null;
        if ($dueDate) {
            // if date is a saturday
            if (Carbon::parse($dueDate)->isSaturday()) {
                $broadcaster = Broadcaster::TV6->value;
            } else {
                $broadcaster = Broadcaster::MTM->value;
            }
            $this->form->fill([
                'due_date' => $dueDate,
                'broadcaster' => $broadcaster,
            ]);
        }
    }

    public function handleStreamingUploadCompleted(...$args): void
    {
        // Get file path from first argument (Livewire 3 passes it as a string)
        $filePath = $args[0] ?? null;

        // Validate file path
        if (! is_string($filePath) || empty($filePath)) {
            return;
        }

        // Extract original name from file path (basename)
        $originalName = basename($filePath);

        // Store in uploadMeta for use in afterCreate()
        $this->uploadMeta = [
            'file_path' => $filePath,
            'original_name' => $originalName,
        ];

        Notification::make()
            ->title('Upload complete')
            ->body('The video file has been attached to this log.')
            ->success()
            ->send();

        try {
            $this->create();
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                Notification::make()
                    ->title('Save failed')
                    ->body('Please complete required fields before saving.')
                    ->danger()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Save failed')
                ->body('Upload finished, but saving the record failed: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function afterCreate(): void
    {
        if (! $this->record) {
            return;
        }

        // Get file path from uploadMeta (set by handleStreamingUploadCompleted)
        $filePath = $this->uploadMeta['file_path'] ?? null;
        $originalName = $this->uploadMeta['original_name'] ?? null;

        if (! $filePath || ! $originalName) {
            $this->uploadMeta = [];

            return;
        }

        // Refresh the record to ensure we have the latest data
        $this->record->refresh();

        try {
            $media = $this->record->replaceVideoFromPath($filePath, $originalName);

            if ($media) {
                // Refresh again to get the updated media_id
                $this->record->refresh();

                VideoLogService::sendDownloadLink($this->record);

                // Refresh form data to show the uploaded file name
                $this->refreshFormData();
            } else {
                Notification::make()
                    ->title('Media save failed')
                    ->body('Failed to save the video file to media library. File may not exist at: '.$filePath)
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Media save error')
                ->body('Error saving media: '.$e->getMessage())
                ->danger()
                ->send();
        }

        $this->uploadMeta = [];
    }
}
