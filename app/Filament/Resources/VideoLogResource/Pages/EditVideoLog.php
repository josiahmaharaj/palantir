<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoLog extends EditRecord
{
    protected static string $resource = VideoLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
