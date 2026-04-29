<?php

namespace App\Filament\Resources\PrinterSettings\Tables;

use App\Models\Branch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrinterSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('branch_id')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('printer_type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('paper_width_mm')
                    ->label('Kertas')
                    ->suffix(' mm'),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('printer_type')
                    ->label('Tipe')
                    ->options([
                        'thermal' => 'Thermal',
                        'inkjet' => 'Inkjet',
                        'laser' => 'Laser',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
