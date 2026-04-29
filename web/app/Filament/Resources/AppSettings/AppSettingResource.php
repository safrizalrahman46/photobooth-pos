<?php

namespace App\Filament\Resources\AppSettings;

use App\Filament\Resources\AppSettings\Pages\EditAppSetting;
use App\Filament\Resources\AppSettings\Pages\ListAppSettings;
use App\Filament\Resources\AppSettings\Pages\ViewAppSetting;
use App\Filament\Resources\AppSettings\Schemas\AppSettingForm;
use App\Filament\Resources\AppSettings\Schemas\AppSettingInfolist;
use App\Filament\Resources\AppSettings\Tables\AppSettingsTable;
use App\Models\AppSetting;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $recordTitleAttribute = 'key';

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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::currentUserCan('settings.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AppSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppSettings::route('/'),
            'view' => ViewAppSetting::route('/{record}'),
            'edit' => EditAppSetting::route('/{record}/edit'),
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
