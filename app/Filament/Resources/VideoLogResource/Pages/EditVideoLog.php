<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use App\Services\VideoLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVideoLog extends EditRecord
{
    protected static string $resource = VideoLogResource::class;

    protected $listeners = [
        'streaming-upload-completed' => 'handleStreamingUploadCompleted',
    ];

    public function handleStreamingUploadCompleted(): void
    {
        $payload = func_get_args()[0] ?? [];
        if (! is_array($payload)) {
            return;
        }

        if ($this->record && filled($this->record->file) && $this->record->file !== $payload['file_path']) {
            VideoLogService::deleteFile($this->record->file);
        }

        $this->form->fill(array_merge($this->form->getState(), [
            'file' => $payload['file_path'],
        ]));

        if ($this->record) {
            $this->record->forceFill(['file' => $payload['file_path']])->save();
            VideoLogService::sendDownloadLink($this->record);
        }

        Notification::make()
            ->title('Upload complete')
            ->body('The video file has been updated.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
