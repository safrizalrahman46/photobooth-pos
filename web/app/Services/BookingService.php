<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingNotificationJob;
use App\Models\Booking;
use App\Models\AddOn;
use App\Models\BookingStatusLog;
use App\Models\Package;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly SlotService $slotService,
    ) {}

    public function create(array $payload): Booking
    {
        return DB::transaction(function () use ($payload): Booking {
            $package = Package::query()->findOrFail($payload['package_id']);
            $addons = $this->resolveAddOnsForPackage((int) $package->id, $payload['addons'] ?? []);
            $addonTotal = collect($addons)->sum('line_total');
            $startAt = Carbon::parse($payload['booking_date'].' '.$payload['booking_time']);
            $endAt = $startAt->copy()->addMinutes((int) $package->duration_minutes);

            $this->assertNoConflict(
                (int) $payload['branch_id'],
                $startAt,
                $endAt
            );

            $booking = Booking::query()->create([
                'booking_code' => $this->codeGenerator->generateBookingCode($startAt),
                'branch_id' => $payload['branch_id'],
                'package_id' => $payload['package_id'],
                'design_catalog_id' => $payload['design_catalog_id'] ?? null,
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'customer_email' => $payload['customer_email'] ?? null,
                'booking_date' => $startAt->toDateString(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => BookingStatus::Pending,
                'source' => $payload['source'] ?? BookingSource::Web,
                'payment_type' => $payload['payment_type'] ?? 'full',
                'addons' => $addons,
                'addon_total' => $addonTotal,
                'total_amount' => (float) $package->base_price + (float) $addonTotal,
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->syncBookingAddOns($booking, $addons);

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => BookingStatus::Pending->value,
                'reason' => 'Booking created',
            ]);

            SendBookingNotificationJob::dispatch($booking->id, 'created');

            $source = $payload['source'] instanceof BookingSource
                ? $payload['source']
                : BookingSource::from((string) ($payload['source'] ?? BookingSource::Web->value));

            if ($source !== BookingSource::Admin) {
                $this->activityLogger->log(
                    'bookings',
                    'created',
                    null,
                    Booking::class,
                    (int) $booking->id,
                    [
                        'message' => sprintf('Booking %s dibuat.', (string) $booking->booking_code),
                        'label' => (string) $booking->booking_code,
                        'source' => (string) ($booking->source?->value ?? $booking->source),
                        'customer_name' => (string) $booking->customer_name,
                        'branch_id' => (int) $booking->branch_id,
                        'package_id' => (int) $booking->package_id,
                        'booking_date' => $booking->booking_date?->toDateString(),
                        'payment_type' => (string) $booking->payment_type,
                        'total_amount' => (float) $booking->total_amount,
                        'add_ons_count' => count($addons),
                    ],
                );
            }

            return $booking;
        });
    }

    public function resolveAddOnsForPackage(int $packageId, array $addons): array
    {
        $requested = collect($addons)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'add_on_id' => (int) ($item['add_on_id'] ?? $item['id'] ?? 0),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                ];
            })
            ->filter(fn (array $item) => $item['add_on_id'] > 0 && $item['qty'] > 0)
            ->groupBy('add_on_id')
            ->map(fn ($group, int|string $addOnId): array => [
                'add_on_id' => (int) $addOnId,
                'qty' => (int) collect($group)->sum('qty'),
            ])
            ->values();

        if ($requested->isEmpty()) {
            return [];
        }

        $ids = $requested->pluck('add_on_id')->all();

        $addOns = AddOn::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty'])
            ->keyBy('id');

        if ($addOns->count() !== count($ids)) {
            throw new RuntimeException('One or more selected add-ons are not available.');
        }

        return $requested
            ->map(function (array $item) use ($addOns, $packageId): array {
                /** @var AddOn|null $addOn */
                $addOn = $addOns->get((int) $item['add_on_id']);

                if (! $addOn) {
                    throw new RuntimeException('One or more selected add-ons are not available.');
                }

                if ($addOn->package_id !== null && (int) $addOn->package_id !== $packageId) {
                    throw new RuntimeException('Selected add-on is not valid for selected package.');
                }

                $qty = (int) $item['qty'];
                $maxQty = max(1, (int) $addOn->max_qty);

                if ($qty > $maxQty) {
                    throw new RuntimeException(sprintf('Maksimum qty untuk %s adalah %d.', (string) $addOn->name, $maxQty));
                }

                $price = max(0, round((float) $addOn->price, 2));

                return [
                    'id' => (int) $addOn->id,
                    'add_on_id' => (int) $addOn->id,
                    'code' => (string) $addOn->code,
                    'label' => (string) $addOn->name,
                    'name' => (string) $addOn->name,
                    'qty' => $qty,
                    'price' => $price,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                ];
            })
            ->values()
            ->all();
    }

    private function syncBookingAddOns(Booking $booking, array $addons): void
    {
        $syncData = [];

        foreach ($addons as $addon) {
            $syncData[(int) $addon['id']] = [
                'qty' => (int) $addon['qty'],
                'unit_price' => (float) $addon['unit_price'],
                'line_total' => (float) $addon['line_total'],
            ];
        }

        $booking->addOns()->sync($syncData);
    }

    public function updateStatus(Booking $booking, BookingStatus $toStatus, ?int $actorId = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $toStatus, $actorId, $reason): Booking {
            $fromStatus = $booking->status instanceof BookingStatus
                ? $booking->status
                : BookingStatus::from($booking->status);

            $booking->status = $toStatus;
            $booking->save();

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'changed_by' => $actorId,
                'reason' => $reason,
            ]);

            SendBookingNotificationJob::dispatch($booking->id, 'status_changed');

            $this->activityLogger->log(
                'bookings',
                'status_changed',
                $actorId,
                Booking::class,
                (int) $booking->id,
                [
                    'message' => sprintf(
                        'Status booking %s berubah dari %s ke %s.',
                        (string) ($booking->booking_code ?? ('BK-' . $booking->id)),
                        $fromStatus->value,
                        $toStatus->value,
                    ),
                    'label' => (string) ($booking->booking_code ?? ('BK-' . $booking->id)),
                    'from_status' => $fromStatus->value,
                    'to_status' => $toStatus->value,
                    'reason' => $reason,
                ],
            );

            return $booking->refresh();
        });
    }

    private function assertNoConflict(int $branchId, Carbon $startAt, Carbon $endAt): void
    {
        $slot = $this->slotService->resolveBookableSlotForSession($branchId, $startAt, $endAt, true);

        if (! $slot) {
            throw new RuntimeException('Slot tidak tersedia atau durasi paket tidak muat pada rentang waktu ini.');
        }

        $remainingCapacity = $this->slotService->remainingParallelCapacity($slot, $startAt, $endAt, null, true);

        if ($remainingCapacity <= 0) {
            throw new RuntimeException('Kapasitas slot sudah penuh. Silakan pilih jadwal lain.');
        }
    }
}
