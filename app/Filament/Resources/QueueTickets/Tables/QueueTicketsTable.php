<?php

namespace App\Filament\Resources\QueueTickets\Tables;

use App\Enums\QueueSourceType;
use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\QueueService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class QueueTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['branch', 'booking']))
            ->defaultSort('queue_date', 'desc')
            ->columns([
                TextColumn::make('queue_code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('queue_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('queue_number')
                    ->label('No')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),
                TextColumn::make('booking.booking_code')
                    ->label('Booking')
                    ->placeholder('-'),
                TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::sourceLabel($state))
                    ->color(fn ($state) => static::sourceColor($state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::statusLabel($state))
                    ->color(fn ($state) => static::statusColor($state)),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(static::branchOptions()),
                SelectFilter::make('status')
                    ->options(static::statusOptions()),
                Filter::make('queue_date')
                    ->form([
                        DatePicker::make('queue_date')
                            ->label('Tanggal antrean')
                            ->default(now()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['queue_date'] ?? null,
                            fn (Builder $query, $date) => $query->whereDate('queue_date', $date),
                        );
                    }),
            ])
            ->recordActions([
                Action::make('call')
                    ->label('Call')
                    ->color('warning')
                    ->visible(fn (QueueTicket $record) => static::currentUserCan('queue.manage') && static::recordStatus($record) === QueueStatus::Waiting->value)
                    ->requiresConfirmation()
                    ->action(fn (QueueTicket $record) => static::transitionTicket($record, QueueStatus::Called, 'Antrean dipanggil.')),
                Action::make('start_session')
                    ->label('Start')
                    ->color('success')
                    ->visible(fn (QueueTicket $record) => static::currentUserCan('queue.manage') && in_array(static::recordStatus($record), [QueueStatus::Called->value, QueueStatus::CheckedIn->value], true))
                    ->requiresConfirmation()
                    ->action(fn (QueueTicket $record) => static::transitionTicket($record, QueueStatus::InSession, 'Sesi foto dimulai.')),
                Action::make('finish')
                    ->label('Finish')
                    ->color('gray')
                    ->visible(fn (QueueTicket $record) => static::currentUserCan('queue.manage') && static::recordStatus($record) === QueueStatus::InSession->value)
                    ->requiresConfirmation()
                    ->action(fn (QueueTicket $record) => static::transitionTicket($record, QueueStatus::Finished, 'Sesi foto selesai.')),
                Action::make('skip')
                    ->label('Skip')
                    ->color('danger')
                    ->visible(fn (QueueTicket $record) => static::currentUserCan('queue.manage') && in_array(static::recordStatus($record), [QueueStatus::Waiting->value, QueueStatus::Called->value, QueueStatus::CheckedIn->value], true))
                    ->requiresConfirmation()
                    ->action(fn (QueueTicket $record) => static::transitionTicket($record, QueueStatus::Skipped, 'Antrean dilewati.')),
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function transitionTicket(QueueTicket $record, QueueStatus $status, string $message): void
    {
        try {
            app(QueueService::class)->transition($record, $status);

            Notification::make()
                ->success()
                ->title($message)
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }

    private static function branchOptions(): array
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function statusOptions(): array
    {
        return [
            QueueStatus::Waiting->value => 'Waiting',
            QueueStatus::Called->value => 'Called',
            QueueStatus::CheckedIn->value => 'Checked In',
            QueueStatus::InSession->value => 'In Session',
            QueueStatus::Finished->value => 'Finished',
            QueueStatus::Skipped->value => 'Skipped',
            QueueStatus::Cancelled->value => 'Cancelled',
        ];
    }

    private static function sourceLabel(mixed $state): string
    {
        $value = $state instanceof QueueSourceType ? $state->value : (string) $state;

        return match ($value) {
            QueueSourceType::Booking->value => 'Booking',
            QueueSourceType::WalkIn->value => 'Walk-in',
            default => ucfirst(str_replace('_', ' ', $value)),
        };
    }

    private static function sourceColor(mixed $state): string
    {
        $value = $state instanceof QueueSourceType ? $state->value : (string) $state;

        return match ($value) {
            QueueSourceType::Booking->value => 'info',
            QueueSourceType::WalkIn->value => 'warning',
            default => 'gray',
        };
    }

    private static function statusLabel(mixed $state): string
    {
        $value = $state instanceof QueueStatus ? $state->value : (string) $state;

        return match ($value) {
            QueueStatus::Waiting->value => 'Waiting',
            QueueStatus::Called->value => 'Called',
            QueueStatus::CheckedIn->value => 'Checked In',
            QueueStatus::InSession->value => 'In Session',
            QueueStatus::Finished->value => 'Finished',
            QueueStatus::Skipped->value => 'Skipped',
            QueueStatus::Cancelled->value => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $value)),
        };
    }

    private static function statusColor(mixed $state): string
    {
        $value = $state instanceof QueueStatus ? $state->value : (string) $state;

        return match ($value) {
            QueueStatus::Waiting->value => 'gray',
            QueueStatus::Called->value => 'warning',
            QueueStatus::CheckedIn->value => 'info',
            QueueStatus::InSession->value => 'success',
            QueueStatus::Finished->value => 'success',
            QueueStatus::Skipped->value, QueueStatus::Cancelled->value => 'danger',
            default => 'gray',
        };
    }

    private static function recordStatus(QueueTicket $record): string
    {
        return $record->status instanceof QueueStatus
            ? $record->status->value
            : (string) $record->status;
    }

    private static function currentUserCan(string $permission): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can($permission);
    }
}
