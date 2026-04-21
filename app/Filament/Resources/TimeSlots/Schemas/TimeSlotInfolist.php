<?php

namespace App\Filament\Resources\TimeSlots\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TimeSlotInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('slot_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                TextEntry::make('start_time')
                    ->label('Jam mulai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                TextEntry::make('end_time')
                    ->label('Jam selesai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                TextEntry::make('capacity')
                    ->label('Kapasitas'),
                IconEntry::make('is_bookable')
                    ->label('Bookable')
                    ->boolean(),
                TextEntry::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
