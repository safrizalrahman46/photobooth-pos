<?php

namespace App\Filament\Resources\QueueTickets\Pages;

use App\Filament\Resources\QueueTickets\QueueTicketResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQueueTicket extends ViewRecord
{
    protected static string $resource = QueueTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
