<?php

namespace App\Filament\Resources\VideoLogResource\Pages;

use App\Broadcaster;
use App\Filament\Resources\VideoLogResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoLog extends CreateRecord
{
    protected static string $resource = VideoLogResource::class;

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
}
