<?php

namespace App\Filament\Resources\Packages\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->placeholder('Global (semua cabang)'),
                TextInput::make('code')
                    ->label('Kode paket')
                    ->required()
                    ->maxLength(40)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
                TextInput::make('name')
                    ->label('Nama paket')
                    ->required()
                    ->maxLength(120),
                TextInput::make('duration_minutes')
                    ->label('Durasi (menit)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(600),
                TextInput::make('base_price')
                    ->label('Harga dasar')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('sort_order')
                    ->label('Urutan tampil')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
