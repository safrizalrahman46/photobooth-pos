<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode cabang')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
                TextInput::make('name')
                    ->label('Nama cabang')
                    ->required()
                    ->maxLength(120),
                TextInput::make('timezone')
                    ->label('Timezone')
                    ->required()
                    ->default('Asia/Jakarta')
                    ->maxLength(64),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->maxLength(30),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
