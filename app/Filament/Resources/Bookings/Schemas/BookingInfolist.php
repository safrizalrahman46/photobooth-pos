<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('booking_code')
                    ->label('Kode booking')
                    ->badge()
                    ->copyable(),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
                TextEntry::make('booking_date')
                    ->label('Tanggal booking')
                    ->date('d M Y'),
                TextEntry::make('start_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('end_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('package.name')
                    ->label('Paket')
                    ->placeholder('-'),
                TextEntry::make('designCatalog.name')
                    ->label('Design')
                    ->placeholder('-'),
                TextEntry::make('customer_name')
                    ->label('Nama customer'),
                TextEntry::make('customer_phone')
                    ->label('Telepon'),
                TextEntry::make('customer_email')
                    ->label('Email')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->label('Sumber'),
                TextEntry::make('payment_type')
                    ->label('Tipe pembayaran')
                    ->placeholder('-'),
                TextEntry::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('paid_amount')
                    ->label('Terbayar')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
