<?php

namespace App\Filament\Resources\QueueTickets;

use App\Filament\Resources\QueueTickets\Pages\ListQueueTickets;
use App\Filament\Resources\QueueTickets\Pages\ViewQueueTicket;
use App\Filament\Resources\QueueTickets\Schemas\QueueTicketForm;
use App\Filament\Resources\QueueTickets\Schemas\QueueTicketInfolist;
use App\Filament\Resources\QueueTickets\Tables\QueueTicketsTable;
use App\Models\QueueTicket;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class QueueTicketResource extends Resource
{
    protected static ?string $model = QueueTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Queue Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $recordTitleAttribute = 'queue_code';

    public static function canAccess(): bool
    {
        return static::currentUserCan('queue.view');
    }

    public static function canViewAny(): bool
    {
        return static::currentUserCan('queue.view');
    }

    public static function canView(Model $record): bool
    {
        return static::currentUserCan('queue.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
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
        return QueueTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QueueTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QueueTicketsTable::configure($table);
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
            'index' => ListQueueTickets::route('/'),
            'view' => ViewQueueTicket::route('/{record}'),
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
