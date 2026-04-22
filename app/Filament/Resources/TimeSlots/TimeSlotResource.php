<?php

namespace App\Filament\Resources\TimeSlots;

use App\Filament\Resources\TimeSlots\Pages\CreateTimeSlot;
use App\Filament\Resources\TimeSlots\Pages\EditTimeSlot;
use App\Filament\Resources\TimeSlots\Pages\ListTimeSlots;
use App\Filament\Resources\TimeSlots\Pages\ViewTimeSlot;
use App\Filament\Resources\TimeSlots\Schemas\TimeSlotForm;
use App\Filament\Resources\TimeSlots\Schemas\TimeSlotInfolist;
use App\Filament\Resources\TimeSlots\Tables\TimeSlotsTable;
use App\Models\TimeSlot;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TimeSlotResource extends Resource
{
    protected static ?string $model = TimeSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Slot Jam';

    protected static ?string $recordTitleAttribute = 'slot_date';

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
        return TimeSlotForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TimeSlotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeSlotsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimeSlots::route('/'),
            'create' => CreateTimeSlot::route('/create'),
            'view' => ViewTimeSlot::route('/{record}'),
            'edit' => EditTimeSlot::route('/{record}/edit'),
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
