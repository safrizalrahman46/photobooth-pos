<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('transaction_code')
                    ->label('Kode transaksi')
                    ->badge()
                    ->copyable(),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('booking.booking_code')
                    ->label('Booking')
                    ->placeholder('-'),
                TextEntry::make('queueTicket.queue_code')
                    ->label('Queue ticket')
                    ->placeholder('-'),
                TextEntry::make('cashier.name')
                    ->label('Kasir')
                    ->placeholder('-'),
                TextEntry::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('discount_amount')
                    ->label('Diskon')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('tax_amount')
                    ->label('Pajak')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('paid_amount')
                    ->label('Terbayar')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('change_amount')
                    ->label('Kembalian')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
