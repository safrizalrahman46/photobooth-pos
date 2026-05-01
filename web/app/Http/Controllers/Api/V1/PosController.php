<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosWalkInCheckoutRequest;
use App\Http\Resources\QueueTicketResource;
use App\Http\Resources\TransactionResource;
use App\Services\PosWalkInCheckoutService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PosController extends Controller
{
    public function __construct(
        private readonly PosWalkInCheckoutService $checkoutService,
        private readonly ApiResponder $responder,
    ) {}

    public function walkInCheckout(PosWalkInCheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->checkoutService->checkout(
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Checkout walk-in gagal.',
                422,
                $exception->errors()
            );
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage() ?: 'Checkout walk-in gagal.', 422);
        }

        return $this->responder->success([
            'transaction' => new TransactionResource($result['transaction']),
            'queue_ticket' => new QueueTicketResource($result['queue_ticket']),
        ], 'Checkout walk-in berhasil.', 201);
    }
}
