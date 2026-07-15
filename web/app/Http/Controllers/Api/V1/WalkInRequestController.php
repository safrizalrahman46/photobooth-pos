<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWalkInRequestPaymentRequest;
use App\Http\Requests\UpdateWalkInRequestRequest;
use App\Http\Resources\QueueTicketResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalkInRequestResource;
use App\Models\Package;
use App\Models\WalkInRequest;
use App\Services\BookingService;
use App\Services\WalkInRequestService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WalkInRequestController extends Controller
{
    public function __construct(
        private readonly WalkInRequestService $service,
        private readonly BookingService $bookingService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.view') || $request->user()?->can('transaction.manage'), 403);

        $rows = $this->service->pendingRows([
            'branch_id' => $request->integer('branch_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'per_page' => $request->integer('per_page', 30),
        ]);

        return $this->responder->paginated(
            $rows,
            WalkInRequestResource::collection($rows),
            'Daftar self walk-in berhasil dimuat.'
        );
    }

    public function update(UpdateWalkInRequestRequest $request, WalkInRequest $walkInRequest): JsonResponse
    {
        $locked = WalkInRequest::query()
            ->whereKey($walkInRequest->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($locked->status !== WalkInRequest::STATUS_PENDING_PAYMENT) {
            return $this->responder->error('Request sudah diproses, tidak bisa diubah.', 422);
        }

        if ($locked->expires_at && $locked->expires_at->isPast()) {
            return $this->responder->error('Request sudah kedaluwarsa.', 422);
        }

        $updateData = [];

        if ($request->has('customer_name')) {
            $updateData['customer_name'] = trim((string) $request->customer_name);
        }

        if ($request->has('customer_phone')) {
            $updateData['customer_phone'] = preg_replace('/\s+/', '', (string) $request->customer_phone);
        }

        if ($request->has('package_id')) {
            /** @var Package $package */
            $package = Package::query()
                ->whereKey((int) $request->package_id)
                ->where('is_active', true)
                ->firstOrFail();

            if ($package->branch_id !== null && (int) $package->branch_id !== (int) $locked->branch_id) {
                return $this->responder->error('Paket tidak tersedia di cabang ini.', 422);
            }

            $updateData['package_id'] = (int) $package->id;
            $updateData['package_name'] = (string) $package->name;
            $updateData['package_price'] = (float) $package->base_price;
        }

        if ($request->has('addons')) {
            $resolvedAddOns = $this->bookingService->resolveAddOnsForPackage(
                (int) ($updateData['package_id'] ?? $locked->package_id),
                $request->addons
            );
            $updateData['add_ons_json'] = $resolvedAddOns;
        }

        $packagePrice = (float) ($updateData['package_price'] ?? $locked->package_price);
        $addOns = $updateData['add_ons_json'] ?? $locked->add_ons_json ?? [];
        $addOnTotal = (float) collect($addOns)->sum('line_total');
        $updateData['total_amount'] = $packagePrice + $addOnTotal;
        $updateData['subtotal_amount'] = $packagePrice + $addOnTotal;

        $locked->update($updateData);

        return $this->responder->success(
            new WalkInRequestResource($locked->fresh()->load(['branch', 'transaction', 'queueTicket'])),
            'Data walk-in berhasil diperbarui.'
        );
    }

    public function confirmPayment(ConfirmWalkInRequestPaymentRequest $request, WalkInRequest $walkInRequest): JsonResponse
    {
        try {
            $result = $this->service->confirmPayment(
                $walkInRequest,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            $message = $exception->validator
                ? ($exception->validator->errors()->first() ?: 'Konfirmasi self walk-in gagal.')
                : ($exception->getMessage() ?: 'Konfirmasi self walk-in gagal.');

            return $this->responder->error($message, 422, $exception->errors());
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage() ?: 'Konfirmasi self walk-in gagal.', 422);
        }

        return $this->responder->success([
            'walk_in_request' => new WalkInRequestResource($result['walk_in_request']),
            'transaction' => new TransactionResource($result['transaction']),
            'queue_ticket' => new QueueTicketResource($result['queue_ticket']),
        ], 'Pembayaran self walk-in berhasil dikonfirmasi.');
    }
}
