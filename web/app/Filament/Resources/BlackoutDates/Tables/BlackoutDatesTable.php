<?php

namespace App\Filament\Resources\BlackoutDates\Tables;

use App\Models\Branch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlackoutDatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('blackout_date', 'desc')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('blackout_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->placeholder('-')
                    ->searchable(),
                IconColumn::make('is_closed')
                    ->label('Tutup penuh')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('is_closed')
                    ->label('Status')
                    ->options([
                        '1' => 'Tutup penuh',
                        '0' => 'Hanya blok tanggal',
                    ]),
                Filter::make('blackout_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('blackout_date')
                            ->label('Tanggal')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['blackout_date'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('blackout_date', $date),
                    )),
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
