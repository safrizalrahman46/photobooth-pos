<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingNotificationJob;
use App\Models\AddOn;
use App\Models\Booking;
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
    ) {}

    public function create(array $payload): Booking
    {
        return DB::transaction(function () use ($payload): Booking {
            $package = Package::query()->findOrFail($payload['package_id']);
<<<<<<< HEAD
            $addons = $this->normalizeAddons($payload['addons'] ?? []);
            $addonTotal = collect($addons)->sum(fn (array $addon) => $addon['price'] * $addon['qty']);
=======
            $selectedAddOns = $this->resolveSelectedAddOns($payload, $package);
            $addOnTotal = (float) collect($selectedAddOns)->sum('line_total');
            $totalAmount = (float) $package->base_price + $addOnTotal;
            [$depositAmount, $paidAmount] = $this->resolveInitialPaymentAmounts($payload, $totalAmount);
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93
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
<<<<<<< HEAD
                'payment_type' => $payload['payment_type'] ?? 'onsite',
                'addons' => $addons,
                'addon_total' => $addonTotal,
                'total_amount' => (float) $package->base_price + (float) $addonTotal,
=======
                'total_amount' => $totalAmount,
                'deposit_amount' => $depositAmount,
                'paid_amount' => $paidAmount,
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93
                'notes' => $payload['notes'] ?? null,
            ]);

            $booking->addOns()->sync($this->buildAddOnSyncData($selectedAddOns));

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => BookingStatus::Pending->value,
                'reason' => 'Booking created',
            ]);

            SendBookingNotificationJob::dispatch($booking->id, 'created');

            return $booking;
        });
    }

<<<<<<< HEAD
    private function normalizeAddons(array $addons): array
    {
        return collect($addons)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    'price' => max(0, round((float) ($item['price'] ?? 0), 2)),
                ];
            })
            ->filter(fn (array $item) => $item['id'] !== '' && $item['label'] !== '' && $item['qty'] > 0)
            ->values()
            ->all();
=======
    private function resolveInitialPaymentAmounts(array $payload, float $totalAmount): array
    {
        $source = $payload['source'] ?? BookingSource::Web;
        $sourceValue = $source instanceof BookingSource ? $source->value : (string) $source;

        if ($sourceValue !== BookingSource::Web->value) {
            return [0.0, 0.0];
        }

        $depositAmount = round($totalAmount * 0.5, 2);

        if (! array_key_exists('payment_type', $payload)) {
            return [$depositAmount, 0.0];
        }

        $paymentType = strtolower(trim((string) ($payload['payment_type'] ?? 'dp50')));

        if ($paymentType === 'full') {
            return [$depositAmount, $totalAmount];
        }

        if ($paymentType === 'dp50') {
            return [$depositAmount, $depositAmount];
        }

        return [$depositAmount, 0.0];
    }

    private function resolveSelectedAddOns(array $payload, Package $package): array
    {
        $requested = collect($payload['add_ons'] ?? [])
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): array => [
                'add_on_id' => (int) ($row['add_on_id'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
            ])
            ->filter(fn (array $row): bool => $row['add_on_id'] > 0 && $row['qty'] > 0)
            ->groupBy('add_on_id')
            ->map(fn ($group, $addOnId): array => [
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
            ->get(['id', 'package_id', 'name', 'price', 'max_qty'])
            ->keyBy('id');

        if ($addOns->count() !== count($ids)) {
            throw new RuntimeException('Ada add-on yang tidak tersedia.');
        }

        $resolved = [];

        foreach ($requested as $item) {
            $addOn = $addOns->get((int) $item['add_on_id']);

            if (! $addOn) {
                throw new RuntimeException('Ada add-on yang tidak tersedia.');
            }

            if ($addOn->package_id !== null && (int) $addOn->package_id !== (int) $package->id) {
                throw new RuntimeException('Add-on tidak valid untuk paket yang dipilih.');
            }

            $maxQty = max(1, (int) $addOn->max_qty);
            $qty = (int) $item['qty'];

            if ($qty > $maxQty) {
                throw new RuntimeException(sprintf('Maksimum qty untuk %s adalah %d.', (string) $addOn->name, $maxQty));
            }

            $unitPrice = (float) $addOn->price;

            $resolved[] = [
                'id' => (int) $addOn->id,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ];
        }

        return $resolved;
    }

    private function buildAddOnSyncData(array $selectedAddOns): array
    {
        $syncData = [];

        foreach ($selectedAddOns as $item) {
            $syncData[(int) $item['id']] = [
                'qty' => (int) $item['qty'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => (float) $item['line_total'],
            ];
        }

        return $syncData;
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93
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

            return $booking->refresh();
        });
    }

    private function assertNoConflict(int $branchId, Carbon $startAt, Carbon $endAt): void
    {
        $hasConflict = Booking::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', BookingStatus::activeStatuses())
            ->where(function ($query) use ($startAt, $endAt) {
                $query->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->lockForUpdate()
            ->exists();

        if ($hasConflict) {
            throw new RuntimeException('Slot sudah terisi. Silakan pilih jadwal lain.');
        }
    }
}
