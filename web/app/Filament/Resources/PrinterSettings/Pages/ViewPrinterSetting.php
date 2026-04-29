<?php

namespace App\Filament\Resources\PrinterSettings\Pages;

use App\Filament\Resources\PrinterSettings\PrinterSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrinterSetting extends ViewRecord
{
    protected static string $resource = PrinterSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
