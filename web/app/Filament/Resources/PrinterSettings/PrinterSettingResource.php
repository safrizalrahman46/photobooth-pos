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
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PrinterSettingResource extends Resource
{
    protected static ?string $model = PrinterSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Printer';

    protected static ?string $recordTitleAttribute = 'device_name';

    public static function canAccess(): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canViewAny(): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canView(Model $record): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canCreate(): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canDeleteAny(): bool
    {
        return static::currentUserCan('settings.manage');
    }

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
        return [];
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

    protected static function currentUserCan(string $permission): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can($permission);
    }
}
