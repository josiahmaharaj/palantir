<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Filament\Resources\VideoLogResource;
use App\Filament\Resources\VideoLogResource\Widgets\CalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoLogs extends ListRecords
{
    protected static string $resource = VideoLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
