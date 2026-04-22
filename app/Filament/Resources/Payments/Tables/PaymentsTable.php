<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['transaction', 'cashier']))
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('payment_code')
                    ->label('Kode pembayaran')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('transaction.transaction_code')
                    ->label('Transaksi')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => static::rupiah($state))
                    ->sortable(),
                TextColumn::make('reference_no')
                    ->label('Referensi')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->placeholder('-'),
                TextColumn::make('paid_at')
                    ->label('Waktu bayar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('Metode')
                    ->options([
                        'cash' => 'Cash',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        'card' => 'Card',
                    ]),
                Filter::make('paid_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('paid_at')
                            ->label('Tanggal bayar')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['paid_at'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('paid_at', $date),
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
