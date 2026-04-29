<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SlotAvailabilityRequest;
use App\Services\SlotService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class SlotAvailabilityController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService,
        private readonly ApiResponder $responder,
    ) {}

    public function __invoke(SlotAvailabilityRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $slots = $this->slotService->getAvailability(
            $payload['date'],
            (int) $payload['package_id'],
            (int) $payload['branch_id']
        );

        return $this->responder->success($slots->values(), 'Ketersediaan slot berhasil dimuat.');
    }
}
