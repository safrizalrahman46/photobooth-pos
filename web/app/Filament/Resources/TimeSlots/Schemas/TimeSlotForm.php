<?php

namespace App\Filament\Resources\TimeSlots\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TimeSlotForm
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
                DatePicker::make('slot_date')
                    ->label('Tanggal')
                    ->required()
                    ->native(false),
                TextInput::make('start_time')
                    ->label('Jam mulai')
                    ->required()
                    ->placeholder('09:00')
                    ->dehydrateStateUsing(fn (?string $state) => static::normalizeTime($state)),
                TextInput::make('end_time')
                    ->label('Jam selesai')
                    ->required()
                    ->placeholder('09:30')
                    ->dehydrateStateUsing(fn (?string $state) => static::normalizeTime($state)),
                TextInput::make('capacity')
                    ->label('Kapasitas')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(1),
                Toggle::make('is_bookable')
                    ->label('Bookable')
                    ->default(true),
            ]);
    }

    private static function normalizeTime(?string $value): string
    {
        $trimmed = trim((string) $value);

        if (preg_match('/^\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed.':00';
        }

        return $trimmed;
    }
}
