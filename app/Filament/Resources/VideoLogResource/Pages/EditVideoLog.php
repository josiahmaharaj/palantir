<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use App\Services\VideoLogService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
            $this->notifyUploadFailed('The video log could not be found. Reload the page and try again.');

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
            $this->notifyUploadFailed('The uploaded file did not arrive. Upload it again.');

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
            if (! $this->record->replaceVideoFromPath($filePath, $originalName)) {
                $this->notifyUploadFailed('The video could not be saved. Upload it again.');

                return;
            }

            // Refresh again to get the updated media_id
            $this->record->refresh();

            $this->notifyDelivery(VideoLogService::sendDownloadLink($this->record));

            // Refresh form data to show the updated file name
            $this->data['upload_path'] = $this->record->videoMedia()?->file_name;
        } catch (Throwable $e) {
            report($e);
            $this->notifyUploadFailed('Something went wrong while saving the video. Upload it again.');
        }
    }

    /**
     * @param  array{sent: int, failed: int}  $result
     */
    private function notifyDelivery(array $result): void
    {
        ['sent' => $sent, 'failed' => $failed] = $result;

        $notification = Notification::make();

        if ($failed > 0) {
            $notification
                ->title("Video saved, {$failed} ".Str::plural('email', $failed).' failed')
                ->body("{$sent} sent. See the audit log for what went wrong.")
                ->danger()
                ->persistent();
        } elseif ($sent === 0) {
            $notification
                ->title('Video saved, nothing sent')
                ->body("{$this->record->broadcaster} has no contacts. Add one, then upload the video again.")
                ->warning()
                ->persistent();
        } else {
            $notification
                ->title("Video sent to {$sent} ".Str::plural('contact', $sent))
                ->body('Each download link works for 7 days. Opens and downloads appear in the audit log.')
                ->icon('heroicon-o-paper-airplane')
                ->success();
        }

        $notification->send();
    }

    private function notifyUploadFailed(string $reason): void
    {
        Notification::make()
            ->title('Upload failed')
            ->body($reason)
            ->danger()
            ->persistent()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
