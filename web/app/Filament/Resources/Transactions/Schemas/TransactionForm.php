<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 2])
            ->components([
                TextInput::make('notes')
                    ->label('Catatan')
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('discount_amount')
                    ->label('Diskon')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
            ]);
    }
}
