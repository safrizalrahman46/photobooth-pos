<?php

namespace App\Filament\Resources\BlackoutDates\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BlackoutDateForm
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
                DatePicker::make('blackout_date')
                    ->label('Tanggal blackout')
                    ->required()
                    ->native(false),
                TextInput::make('reason')
                    ->label('Alasan')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('is_closed')
                    ->label('Studio tutup penuh')
                    ->default(true),
            ]);
    }
}
