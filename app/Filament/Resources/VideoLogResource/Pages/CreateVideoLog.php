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

    public function handleStreamingUploadCompleted(): void
    {
        $payload = func_get_args()[0] ?? [];
        if (! is_array($payload)) {
            return;
        }

        $this->uploadMeta = $payload;
        $this->form->fill(array_merge($this->form->getState(), [
            'file' => $payload['file_path'],
        ]));

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
        if ($this->record && filled($this->record->file)) {
            VideoLogService::sendDownloadLink($this->record);
        }
    }
}
