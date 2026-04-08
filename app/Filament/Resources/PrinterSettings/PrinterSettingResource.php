<?php

namespace App\Filament\Resources\PrinterSettings;

use App\Filament\Resources\PrinterSettings\Pages\CreatePrinterSetting;
use App\Filament\Resources\PrinterSettings\Pages\EditPrinterSetting;
use App\Filament\Resources\PrinterSettings\Pages\ListPrinterSettings;
use App\Filament\Resources\PrinterSettings\Pages\ViewPrinterSetting;
use App\Filament\Resources\PrinterSettings\Schemas\PrinterSettingForm;
use App\Filament\Resources\PrinterSettings\Schemas\PrinterSettingInfolist;
use App\Filament\Resources\PrinterSettings\Tables\PrinterSettingsTable;
use App\Models\PrinterSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrinterSettingResource extends Resource
{
    protected static ?string $model = PrinterSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'device_name';

    public static function form(Schema $schema): Schema
    {
        return PrinterSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrinterSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrinterSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrinterSettings::route('/'),
            'create' => CreatePrinterSetting::route('/create'),
            'view' => ViewPrinterSetting::route('/{record}'),
            'edit' => EditPrinterSetting::route('/{record}/edit'),
        ];
    }
}
