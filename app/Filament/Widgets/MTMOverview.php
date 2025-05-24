<?php

namespace App\Filament\Widgets;

use App\Broadcaster;
use App\Models\VideoLog;
use App\Status;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MTMOverview extends BaseWidget
{
    protected function getStats(): array
    {

        $latest = VideoLog::where('broadcaster', Broadcaster::MTM->value)->latest()->first();
        $log = $latest->log_id;
        $nextDate = $latest->due_date;
        $status = Status::Todo->value;
        if ($nextDate < now()) {
            $nextDate = $nextDate->addWeek();
            $log = $log + 1;
        }

        return [
            Stat::make('Next MTM', $log . ': ' . $nextDate->format('jS M, Y'))
                ->description($status)
                ->color('success'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'MTM Overview';
    }

    protected function getDescription(): ?string
    {
        return 'Overview of the latest MTM Programme';
    }
}
