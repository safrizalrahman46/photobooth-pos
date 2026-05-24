<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Package;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\WalkInRequest;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalkInRequestService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly QueueService $queueService,
        private readonly TransactionService $transactionService,
        private readonly InventoryService $inventoryService,
        private readonly CodeGenerator $codeGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function create(array $payload): WalkInRequest
    {
        return DB::transaction(function () use ($payload): WalkInRequest {
            $submissionKey = trim((string) ($payload['submission_key'] ?? ''));

            if ($submissionKey !== '') {
                $existing = WalkInRequest::query()
                    ->where('submission_key', $submissionKey)
                    ->first();

                if ($existing) {
                    return $existing->load(['branch', 'transaction', 'queueTicket']);
                }
            }

            /** @var Branch $branch */
            $branch = Branch::query()
                ->whereKey((int) $payload['branch_id'])
                ->where('is_active', true)
                ->firstOrFail();

            /** @var Package $package */
            $package = Package::query()
                ->whereKey((int) $payload['package_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $this->assertPackageAvailableForBranch($package, (int) $branch->id);

            $selectedAddOns = $this->bookingService->resolveAddOnsForPackage(
                (int) $package->id,
                $payload['addons'] ?? []
            );
            $packagePrice = (float) $package->base_price;
            $subtotal = $packagePrice + (float) collect($selectedAddOns)->sum('line_total');
            $now = Carbon::now($this->queueTimezone());
            $expiresAt = $now->copy()->addMinutes($this->requestTtlMinutes());

            /** @var WalkInRequest $walkInRequest */
            $walkInRequest = WalkInRequest::query()->create([
                'request_code' => $this->codeGenerator->generateWalkInRequestCode($now),
                'branch_id' => (int) $branch->id,
                'package_id' => (int) $package->id,
                'package_name' => (string) $package->name,
                'package_price' => $packagePrice,
                'customer_name' => trim((string) $payload['customer_name']),
                'customer_phone' => preg_replace('/\s+/', '', (string) $payload['customer_phone']),
                'add_ons_json' => $selectedAddOns,
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
                'status' => WalkInRequest::STATUS_PENDING_PAYMENT,
                'expires_at' => $expiresAt,
                'submission_key' => $submissionKey !== '' ? $submissionKey : null,
                'request_ip' => $payload['request_ip'] ?? null,
                'user_agent' => isset($payload['user_agent']) ? mb_substr((string) $payload['user_agent'], 0, 500) : null,
            ]);

            $this->activityLogger->log(
                'walk_in_requests',
                'created',
                null,
                WalkInRequest::class,
                (int) $walkInRequest->id,
                [
                    'message' => sprintf('Self walk-in %s dibuat.', (string) $walkInRequest->request_code),
                    'label' => (string) $walkInRequest->request_code,
                    'branch_id' => (int) $walkInRequest->branch_id,
                    'package_id' => (int) $walkInRequest->package_id,
                    'total_amount' => $subtotal,
                    'status' => WalkInRequest::STATUS_PENDING_PAYMENT,
                ],
            );

            return $walkInRequest->refresh()->load(['branch', 'transaction', 'queueTicket']);
        });
    }

    public function pendingRows(array $filters = []): LengthAwarePaginator
    {
        $this->expirePendingRequests();

        $query = WalkInRequest::query()
            ->with(['branch', 'transaction', 'queueTicket'])
            ->orderByDesc('created_at');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        } else {
            $query->whereIn('status', [
                WalkInRequest::STATUS_PENDING_PAYMENT,
                WalkInRequest::STATUS_PAID,
            ]);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('request_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $today = Carbon::now($this->queueTimezone());
        $query->whereBetween('created_at', [
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        ]);

        return $query->paginate(max(1, min((int) ($filters['per_page'] ?? 30), 100)));
    }

    public function confirmPayment(WalkInRequest $walkInRequest, array $payload, int $cashierId): array
    {
        return DB::transaction(function () use ($walkInRequest, $payload, $cashierId): array {
            /** @var WalkInRequest $lockedRequest */
            $lockedRequest = WalkInRequest::query()
                ->whereKey($walkInRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status === WalkInRequest::STATUS_PAID && $lockedRequest->transaction_id && $lockedRequest->queue_ticket_id) {
                return $this->confirmedPayload($lockedRequest);
            }

            if ($lockedRequest->status !== WalkInRequest::STATUS_PENDING_PAYMENT) {
                throw ValidationException::withMessages([
                    'status' => 'Request walk-in tidak dalam status menunggu pembayaran.',
                ]);
            }

            $now = Carbon::now($this->queueTimezone());

            if ($lockedRequest->expires_at && $lockedRequest->expires_at->lessThanOrEqualTo($now)) {
                $lockedRequest->update(['status' => WalkInRequest::STATUS_EXPIRED]);

                throw ValidationException::withMessages([
                    'expires_at' => 'Request walk-in sudah kedaluwarsa. Minta customer scan QR dan isi ulang.',
                ]);
            }

            /** @var Branch $branch */
            $branch = Branch::query()
                ->whereKey((int) $lockedRequest->branch_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Package $package */
            $package = Package::query()
                ->whereKey((int) $lockedRequest->package_id)
                ->where('is_active', true)
                ->firstOrFail();

            $this->assertPackageAvailableForBranch($package, (int) $branch->id);

            /** @var QueueTicket $queueTicket */
            $queueTicket = $this->queueService->createWalkIn([
                'branch_id' => (int) $lockedRequest->branch_id,
                'queue_date' => $this->queueTodayDate(),
                'customer_name' => (string) $lockedRequest->customer_name,
                'customer_phone' => (string) $lockedRequest->customer_phone,
            ]);

            /** @var Transaction $transaction */
            $transaction = $this->transactionService->create([
                'branch_id' => (int) $lockedRequest->branch_id,
                'queue_ticket_id' => (int) $queueTicket->id,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'notes' => $payload['notes'] ?? sprintf('Self walk-in QR %s.', (string) $lockedRequest->request_code),
                'items' => $this->transactionItemsFromRequest($lockedRequest),
            ], $cashierId);

            $transaction = $this->transactionService->addPayment($transaction, [
                'method' => 'cash',
                'amount' => (float) $lockedRequest->total_amount,
                'reference_no' => $payload['reference_no'] ?? null,
                'notes' => 'Self walk-in QR cash payment.',
                'meta' => [
                    'type' => 'self_walk_in_qr',
                    'walk_in_request_id' => (int) $lockedRequest->id,
                    'request_code' => (string) $lockedRequest->request_code,
                ],
            ], $cashierId);

            $this->inventoryService->deductForTransaction(
                $transaction,
                $cashierId,
                InventoryService::SOURCE_SELF_WALK_IN_TRANSACTION
            );

            $lockedRequest->update([
                'status' => WalkInRequest::STATUS_PAID,
                'paid_at' => now(),
                'confirmed_by' => $cashierId,
                'transaction_id' => (int) $transaction->id,
                'queue_ticket_id' => (int) $queueTicket->id,
            ]);

            $this->activityLogger->log(
                'walk_in_requests',
                'payment_confirmed',
                $cashierId,
                WalkInRequest::class,
                (int) $lockedRequest->id,
                [
                    'message' => sprintf('Self walk-in %s dibayar dan masuk antrean %s.', (string) $lockedRequest->request_code, (string) $queueTicket->queue_code),
                    'label' => (string) $lockedRequest->request_code,
                    'transaction_code' => (string) $transaction->transaction_code,
                    'queue_code' => (string) $queueTicket->queue_code,
                    'total_amount' => (float) $lockedRequest->total_amount,
                ],
            );

            return $this->confirmedPayload($lockedRequest->refresh());
        });
    }

    public function expirePendingRequests(): int
    {
        return WalkInRequest::query()
            ->where('status', WalkInRequest::STATUS_PENDING_PAYMENT)
            ->where('expires_at', '<=', Carbon::now($this->queueTimezone()))
            ->update(['status' => WalkInRequest::STATUS_EXPIRED]);
    }

    private function confirmedPayload(WalkInRequest $walkInRequest): array
    {
        $walkInRequest->load(['branch', 'transaction.branch', 'transaction.queueTicket', 'transaction.items', 'transaction.payments', 'queueTicket.branch']);

        return [
            'walk_in_request' => $walkInRequest,
            'transaction' => $walkInRequest->transaction,
            'queue_ticket' => $walkInRequest->queueTicket,
        ];
    }

    private function transactionItemsFromRequest(WalkInRequest $walkInRequest): array
    {
        $items = [[
            'item_type' => 'package',
            'item_ref_id' => (int) $walkInRequest->package_id,
            'item_name' => (string) $walkInRequest->package_name,
            'qty' => 1,
            'unit_price' => (float) $walkInRequest->package_price,
        ]];

        foreach ($walkInRequest->add_ons_json ?? [] as $addOn) {
            $items[] = [
                'item_type' => 'add_on',
                'item_ref_id' => (int) ($addOn['id'] ?? $addOn['add_on_id'] ?? 0),
                'item_name' => (string) ($addOn['name'] ?? $addOn['label'] ?? 'Add-on'),
                'qty' => max(1, (int) ($addOn['qty'] ?? 1)),
                'unit_price' => max(0, (float) ($addOn['unit_price'] ?? $addOn['price'] ?? 0)),
            ];
        }

        return $items;
    }

    private function assertPackageAvailableForBranch(Package $package, int $branchId): void
    {
        if ($package->branch_id !== null && (int) $package->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'package_id' => 'Paket tidak tersedia untuk cabang ini.',
            ]);
        }
    }

    private function queueTodayDate(): string
    {
        return Carbon::now($this->queueTimezone())->toDateString();
    }

    private function queueTimezone(): string
    {
        return (string) config('app.queue_timezone', 'Asia/Jakarta');
    }

    private function requestTtlMinutes(): int
    {
        return max(5, (int) config('app.walk_in_request_ttl_minutes', 30));
    }
}
