<?php

namespace App\Filament\Resources\VideoLogResource\Widgets;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource;
use App\Models\VideoLog;
use App\Status;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    // protected static string $view = 'filament.resources.video-log-resource.widgets.calendar-widget';

    public function fetchEvents(array $fetchInfo): array
    {
        return VideoLog::query()
            ->where('due_date', '>=', Carbon::create(now()->year, now()->month, 1)->startOfMonth())
            ->where('due_date', '<=', Carbon::create(now()->year, now()->month, 1)->endOfMonth())
            ->get()
            ->map(
                fn(VideoLog $log) => EventData::make()
                    ->id($log->id)
                    ->title($log->title)
                    ->start($log->due_date)
                    ->end($log->due_date)
                    ->backgroundColor($log->broadcaster == Broadcaster::MTM->value ? '#17479f' : '#de222a')
                    ->borderColor($log->status == Status::Todo->value ? '#00FF00' : '')
                    ->allDay(true)
                    ->url(url: VideoLogResource::getUrl(name: 'edit', parameters: ['record' => $log->id]))
            )->toArray();
    }
}
