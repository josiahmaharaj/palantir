<?php

namespace App\Filament\Widgets;

use App\Broadcaster;
use App\Models\VideoLog;
use App\Status;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TV6Overview extends BaseWidget
{
    protected function getStats(): array
    {

        $latest = VideoLog::where('broadcaster', Broadcaster::TV6->value)->latest()->first();
        $log = $latest->log_id;
        $nextDate = $latest->due_date;
        $status = Status::Todo->value;
        if ($nextDate < now()) {
            $nextDate = $nextDate->addWeeks(2);
            $log = $log + 1;
        }

        return [
            Stat::make('Next TV6', $log . ': ' . $nextDate->format('jS M, Y'))
                ->description($status)
                ->color('success'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'TV6 Overview';
    }

    protected function getDescription(): ?string
    {
        return 'Overview of the latest TV6 Programme';
    }
}
