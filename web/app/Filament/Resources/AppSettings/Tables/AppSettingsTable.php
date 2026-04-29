<?php

namespace App\Filament\Resources\AppSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Grup')
                    ->badge()
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Properti')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '-'),
                TextColumn::make('updater.name')
                    ->label('Diperbarui oleh')
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label('Update terakhir')
                    ->since()
                    ->placeholder('-'),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
