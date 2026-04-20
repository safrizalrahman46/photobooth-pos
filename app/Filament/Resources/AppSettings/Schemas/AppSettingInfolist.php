<?php

namespace App\Filament\Resources\AppSettings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('key')
                    ->label('Grup pengaturan'),
                TextEntry::make('updater.name')
                    ->label('Terakhir diubah oleh')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Terakhir diubah')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextEntry::make('value')
                    ->label('Isi pengaturan')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
                    
            ]);
    }
}
