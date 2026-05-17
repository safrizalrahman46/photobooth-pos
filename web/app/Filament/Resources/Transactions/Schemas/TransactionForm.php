<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Schemas\Schema;

class TransactionForm
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
