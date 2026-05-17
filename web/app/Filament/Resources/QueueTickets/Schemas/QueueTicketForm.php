<?php

namespace App\Filament\Resources\QueueTickets\Schemas;

use Filament\Schemas\Schema;

class QueueTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 2])
            ->components([
                //
            ]);
    }
}
