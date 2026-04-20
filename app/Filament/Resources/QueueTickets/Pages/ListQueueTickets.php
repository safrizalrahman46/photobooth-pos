<?php

namespace App\Filament\Resources\QueueTickets\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\QueueTickets\QueueTicketResource;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Services\QueueService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ListQueueTickets extends ListRecords
{
    protected static string $resource = QueueTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('booking_check_in')
                ->label('Check-in Booking')
                ->color('info')
                ->visible(fn () => $this->currentUserCan('queue.manage'))
                ->form([
                    TextInput::make('booking_code')
                        ->label('Kode booking')
                        ->placeholder('BKG-YYYYMMDD-0001')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $booking = Booking::query()
                        ->where('booking_code', trim((string) $data['booking_code']))
                        ->with('queueTicket')
                        ->first();

                    if (! $booking) {
                        Notification::make()->danger()->title('Booking tidak ditemukan.')->send();

                        return;
                    }

                    if ($booking->queueTicket) {
                        Notification::make()->warning()->title('Booking ini sudah masuk antrean.')->send();

                        return;
                    }

                    if (in_array($booking->status?->value ?? $booking->status, [BookingStatus::Cancelled->value, BookingStatus::Done->value], true)) {
                        Notification::make()->danger()->title('Status booking tidak bisa di-check-in.')->send();

                        return;
                    }

                    try {
                        app(QueueService::class)->checkInBooking($booking);

                        Notification::make()->success()->title('Booking berhasil masuk antrean.')->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('walk_in')
                ->label('Tambah Walk-in')
                ->color('success')
                ->visible(fn () => $this->currentUserCan('queue.manage'))
                ->form([
                    Select::make('branch_id')
                        ->label('Cabang')
                        ->options($this->branchOptions())
                        ->required(),
                    DatePicker::make('queue_date')
                        ->label('Tanggal antrean')
                        ->default(now()->toDateString())
                        ->required(),
                    TextInput::make('customer_name')
                        ->label('Nama pelanggan')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('customer_phone')
                        ->label('No. WhatsApp')
                        ->maxLength(30),
                ])
                ->action(function (array $data): void {
                    try {
                        app(QueueService::class)->createWalkIn($data);

                        Notification::make()->success()->title('Antrean walk-in berhasil dibuat.')->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('call_next')
                ->label('Call Next')
                ->color('warning')
                ->visible(fn () => $this->currentUserCan('queue.manage'))
                ->form([
                    Select::make('branch_id')
                        ->label('Cabang')
                        ->options($this->branchOptions())
                        ->required(),
                    DatePicker::make('queue_date')
                        ->label('Tanggal antrean')
                        ->default(now()->toDateString())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $ticket = app(QueueService::class)->callNext((int) $data['branch_id'], (string) $data['queue_date']);
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();

                        return;
                    }

                    if (! $ticket) {
                        Notification::make()->warning()->title('Tidak ada antrean waiting untuk dipanggil.')->send();

                        return;
                    }

                    Notification::make()->success()->title('Antrean berikutnya berhasil dipanggil.')->send();
                }),
        ];
    }

    private function branchOptions(): array
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function currentUserCan(string $permission): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can($permission);
    }
}
