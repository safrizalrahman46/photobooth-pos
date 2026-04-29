<?php

namespace App\Filament\Resources\PrinterSettings\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PrinterSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('device_name')
                    ->label('Nama device')
                    ->required()
                    ->maxLength(120),
                Select::make('printer_type')
                    ->label('Tipe printer')
                    ->required()
                    ->options([
                        'thermal' => 'Thermal',
                        'inkjet' => 'Inkjet',
                        'laser' => 'Laser',
                    ])
                    ->default('thermal'),
                TextInput::make('paper_width_mm')
                    ->label('Lebar kertas (mm)')
                    ->required()
                    ->numeric()
                    ->default(80)
                    ->minValue(58)
                    ->maxValue(120),
                Toggle::make('is_default')
                    ->label('Default printer')
                    ->default(false),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                KeyValue::make('connection')
                    ->label('Konfigurasi koneksi')
                    ->keyLabel('Kunci')
                    ->valueLabel('Nilai')
                    ->columnSpanFull(),
            ]);
    }
}
