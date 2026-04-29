<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('payment_code')
                    ->label('Kode pembayaran')
                    ->badge()
                    ->copyable(),
                TextEntry::make('transaction.transaction_code')
                    ->label('Kode transaksi')
                    ->placeholder('-'),
                TextEntry::make('method')
                    ->label('Metode')
                    ->badge(),
                TextEntry::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('reference_no')
                    ->label('Referensi')
                    ->placeholder('-'),
                TextEntry::make('cashier.name')
                    ->label('Kasir')
                    ->placeholder('-'),
                TextEntry::make('paid_at')
                    ->label('Waktu bayar')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
