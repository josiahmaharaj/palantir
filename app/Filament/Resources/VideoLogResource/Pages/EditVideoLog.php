<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use App\Services\VideoLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditVideoLog extends EditRecord
{
    protected static string $resource = VideoLogResource::class;

    protected $listeners = [
        'streaming-upload-completed' => 'handleStreamingUploadCompleted',
    ];

    public function handleStreamingUploadCompleted(...$args): void
    {
        // Log everything we receive
        Log::info('EditVideoLog::handleStreamingUploadCompleted - START', [
            'all_args' => $args,
            'args_count' => count($args),
            'first_arg' => $args[0] ?? 'NOT_SET',
            'record_exists' => $this->record !== null,
        ]);

        if (! $this->record) {
            Log::warning('EditVideoLog::handleStreamingUploadCompleted - No record');

            Notification::make()
                ->title('Upload failed')
                ->body('No record found.')
                ->danger()
                ->send();

            return;
        }

        // Get file path from first argument (Livewire 3 passes it as a string)
        $filePath = $args[0] ?? null;

        // Validate file path
        if (! is_string($filePath) || empty($filePath)) {
            Log::warning('EditVideoLog::handleStreamingUploadCompleted - Invalid file path', [
                'file_path' => $filePath,
                'type' => gettype($filePath),
            ]);

            Notification::make()
                ->title('Upload failed')
                ->body('Invalid file path received.')
                ->danger()
                ->send();

            return;
        }

        // Extract original name from file path (basename)
        $originalName = basename($filePath);

        Log::info('EditVideoLog::handleStreamingUploadCompleted - Processing upload', [
            'file_path' => $filePath,
            'original_name' => $originalName,
        ]);

        // Refresh the record to ensure we have the latest data
        $this->record->refresh();

        try {
            $media = $this->record->replaceVideoFromPath($filePath, $originalName);

            if ($media) {
                // Refresh again to get the updated media_id
                $this->record->refresh();

                VideoLogService::sendDownloadLink($this->record);

                Notification::make()
                    ->title('Upload complete')
                    ->body('The video file has been updated.')
                    ->success()
                    ->send();

                // Refresh form data to show the updated file name
                $this->refreshFormData();
            } else {
                Notification::make()
                    ->title('Upload failed')
                    ->body('Failed to save the video file to media library. File may not exist at: '.$filePath)
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Upload error')
                ->body('Error saving media: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
