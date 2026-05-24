<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWalkInRequestPaymentRequest;
use App\Http\Resources\QueueTicketResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalkInRequestResource;
use App\Models\WalkInRequest;
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

    public function confirmPayment(ConfirmWalkInRequestPaymentRequest $request, WalkInRequest $walkInRequest): JsonResponse
    {
        try {
            $result = $this->service->confirmPayment(
                $walkInRequest,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Konfirmasi self walk-in gagal.',
                422,
                $exception->errors()
            );
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
