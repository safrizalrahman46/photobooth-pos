<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code')
                    ->label('Kode paket')
                    ->badge()
                    ->copyable(),
                TextEntry::make('name')
                    ->label('Nama paket'),
                TextEntry::make('branch.name')
                    ->label('Cabang')
                    ->placeholder('Global'),
                TextEntry::make('duration_minutes')
                    ->label('Durasi')
                    ->suffix(' menit'),
                TextEntry::make('base_price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('sort_order')
                    ->label('Urutan tampil'),
                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextEntry::make('description')
                    ->label('Deskripsi')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
