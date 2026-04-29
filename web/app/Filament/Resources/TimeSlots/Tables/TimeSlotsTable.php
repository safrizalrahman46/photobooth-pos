<?php

namespace App\Filament\Resources\TimeSlots\Tables;

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

class TimeSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('branch'))
            ->defaultSort('slot_date', 'desc')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('slot_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5))
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5))
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable(),
                IconColumn::make('is_bookable')
                    ->label('Bookable')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(static fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('is_bookable')
                    ->label('Status')
                    ->options([
                        '1' => 'Bookable',
                        '0' => 'Blocked',
                    ]),
                Filter::make('slot_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('slot_date')
                            ->label('Tanggal')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['slot_date'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('slot_date', $date),
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
