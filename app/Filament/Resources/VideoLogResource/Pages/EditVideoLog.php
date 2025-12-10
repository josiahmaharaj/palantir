<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use App\Services\VideoLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditVideoLog extends EditRecord
{
    protected static string $resource = VideoLogResource::class;

    protected $listeners = [
        'streaming-upload-completed' => 'handleStreamingUploadCompleted',
    ];

    public function handleStreamingUploadCompleted(): void
    {
        $filepath = func_get_args()[0] ?? [];
        if (! $filepath) {
            return;
        }

        if ($this->record && filled($this->record->file) && $this->record->file !== $filepath) {
            VideoLogService::deleteFile($this->record->file);
        }

        $this->form->fill(array_merge($this->form->getState(), [
            'file' => $filepath,
        ]));

        if ($this->record) {
            $this->record->forceFill(['file' => $filepath])->save();
            VideoLogService::sendDownloadLink($this->record);
        }

        Notification::make()
            ->title('Upload complete')
            ->body('The video file has been updated.')
            ->success()
            ->send();

        $this->refreshFormData(['file']);

        try {
            $this->save();
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
