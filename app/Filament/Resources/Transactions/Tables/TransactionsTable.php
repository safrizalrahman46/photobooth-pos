<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['branch', 'booking', 'cashier']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_code')
                    ->label('Kode transaksi')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('booking.booking_code')
                    ->label('Booking')
                    ->placeholder('-'),
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => static::rupiah($state)),
                TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->formatStateUsing(fn ($state) => static::rupiah($state)),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'void' => 'Void',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('Tanggal transaksi')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['date'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('created_at', $date),
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
