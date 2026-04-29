<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['branch', 'package']))
            ->defaultSort('booking_date', 'desc')
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode booking')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('booking_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('package.name')
                    ->label('Paket')
                    ->placeholder('-'),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => static::rupiah($state)),
                TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->formatStateUsing(fn ($state) => static::rupiah($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'paid' => 'Paid',
                        'checked_in' => 'Checked In',
                        'in_queue' => 'In Queue',
                        'in_session' => 'In Session',
                        'done' => 'Done',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('booking_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('booking_date')
                            ->label('Tanggal booking')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['booking_date'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('booking_date', $date),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function rupiah(mixed $state): string
    {
        $value = is_numeric($state) ? (float) $state : 0;

        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
