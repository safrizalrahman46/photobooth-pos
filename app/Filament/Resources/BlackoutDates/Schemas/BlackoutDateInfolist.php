<?php

namespace App\Filament\Resources\BlackoutDates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BlackoutDateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('blackout_date')
                    ->label('Tanggal blackout')
                    ->date('d M Y'),
                TextEntry::make('reason')
                    ->label('Alasan')
                    ->placeholder('-'),
                IconEntry::make('is_closed')
                    ->label('Tutup penuh')
                    ->boolean(),
                TextEntry::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
