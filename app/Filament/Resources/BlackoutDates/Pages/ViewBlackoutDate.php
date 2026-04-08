<?php

namespace App\Filament\Resources\BlackoutDates\Pages;

use App\Filament\Resources\BlackoutDates\BlackoutDateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBlackoutDate extends ViewRecord
{
    protected static string $resource = BlackoutDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
