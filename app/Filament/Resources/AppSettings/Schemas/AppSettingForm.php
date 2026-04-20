<?php

namespace App\Filament\Resources\AppSettings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Grup')
                    ->disabled()
                    ->dehydrated(false),
                KeyValue::make('value')
                    ->label('Nilai pengaturan')
                    ->keyLabel('Properti')
                    ->valueLabel('Nilai')
                    ->columnSpanFull(),
            ]);
    }
}
