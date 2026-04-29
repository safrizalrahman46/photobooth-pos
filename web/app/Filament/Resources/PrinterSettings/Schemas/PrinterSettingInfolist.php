<?php

namespace App\Filament\Resources\PrinterSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PrinterSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')
                    ->label('Cabang'),
                TextEntry::make('device_name')
                    ->label('Nama device'),
                TextEntry::make('printer_type')
                    ->label('Tipe printer')
                    ->badge(),
                TextEntry::make('paper_width_mm')
                    ->label('Lebar kertas')
                    ->suffix(' mm'),
                IconEntry::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                KeyValueEntry::make('connection')
                    ->label('Konfigurasi koneksi')
                    ->columnSpanFull(),
            ]);
    }
}
