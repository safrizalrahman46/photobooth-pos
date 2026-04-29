<?php

namespace App\Filament\Resources\Packages\Tables;

use App\Models\Branch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->label('Nama paket')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->placeholder('Global')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Durasi')
                    ->suffix(' menit')
                    ->sortable(),
                TextColumn::make('base_price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => static::rupiah($state))
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
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

    private static function rupiah(mixed $state): string
    {
        $value = is_numeric($state) ? (float) $state : 0;

        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
