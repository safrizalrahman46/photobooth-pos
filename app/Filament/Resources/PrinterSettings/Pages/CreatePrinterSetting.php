<?php

namespace App\Filament\Resources\PrinterSettings\Pages;

use App\Filament\Resources\PrinterSettings\PrinterSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrinterSetting extends CreateRecord
{
    protected static string $resource = PrinterSettingResource::class;
}
