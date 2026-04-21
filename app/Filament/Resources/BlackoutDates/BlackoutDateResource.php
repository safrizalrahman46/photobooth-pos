<?php

namespace App\Filament\Resources\BlackoutDates;

use App\Filament\Resources\BlackoutDates\Pages\CreateBlackoutDate;
use App\Filament\Resources\BlackoutDates\Pages\EditBlackoutDate;
use App\Filament\Resources\BlackoutDates\Pages\ListBlackoutDates;
use App\Filament\Resources\BlackoutDates\Pages\ViewBlackoutDate;
use App\Filament\Resources\BlackoutDates\Schemas\BlackoutDateForm;
use App\Filament\Resources\BlackoutDates\Schemas\BlackoutDateInfolist;
use App\Filament\Resources\BlackoutDates\Tables\BlackoutDatesTable;
use App\Models\BlackoutDate;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BlackoutDateResource extends Resource
{
    protected static ?string $model = BlackoutDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Blackout Date';

    protected static ?string $recordTitleAttribute = 'blackout_date';

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
        return BlackoutDateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BlackoutDateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlackoutDatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlackoutDates::route('/'),
            'create' => CreateBlackoutDate::route('/create'),
            'view' => ViewBlackoutDate::route('/{record}'),
            'edit' => EditBlackoutDate::route('/{record}/edit'),
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
