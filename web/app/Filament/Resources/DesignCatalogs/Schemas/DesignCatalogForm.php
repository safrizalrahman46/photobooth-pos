<?php

namespace App\Filament\Resources\DesignCatalogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DesignCatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 2])
            ->components([
                Select::make('package_id')
                    ->label('Paket')
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih paket'),
                TextInput::make('name')
                    ->label('Nama desain')
                    ->required()
                    ->maxLength(120),
                TextInput::make('preview_url')
                    ->label('URL pratinjau')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://contoh.com/gambar.jpg'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
