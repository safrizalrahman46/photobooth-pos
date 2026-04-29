<?php

namespace App\Filament\Resources\QueueTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QueueTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('queue_code')
                    ->label('Kode antrean')
                    ->copyable(),
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('queue_number')
                    ->label('Nomor antrean'),
                TextEntry::make('customer_name')
                    ->label('Nama pelanggan'),
                TextEntry::make('customer_phone')
                    ->label('No. WhatsApp')
                    ->placeholder('-'),
                TextEntry::make('booking.booking_code')
                    ->label('Kode booking')
                    ->placeholder('-'),
                TextEntry::make('source_type')
                    ->label('Sumber')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('queue_date')
                    ->label('Tanggal antrean')
                    ->date('d M Y'),
                TextEntry::make('called_at')
                    ->label('Dipanggil')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextEntry::make('checked_in_at')
                    ->label('Check-in')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextEntry::make('started_at')
                    ->label('Mulai sesi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextEntry::make('finished_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
