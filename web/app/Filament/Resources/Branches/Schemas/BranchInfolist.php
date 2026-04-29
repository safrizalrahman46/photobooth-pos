<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code')
                    ->label('Kode cabang')
                    ->badge()
                    ->copyable(),
                TextEntry::make('name')
                    ->label('Nama cabang'),
                TextEntry::make('timezone')
                    ->label('Timezone'),
                TextEntry::make('phone')
                    ->label('Telepon')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('Alamat')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
