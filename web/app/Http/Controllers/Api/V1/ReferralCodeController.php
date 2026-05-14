<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateReferralCodeRequest;
use App\Services\ReferralService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReferralCodeController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly ApiResponder $responder,
    ) {}

    public function validateCode(ValidateReferralCodeRequest $request): JsonResponse
    {
        $payload = $request->validated();

        try {
            $preview = $this->referralService->preview(
                (string) $payload['referral_code'],
                (int) $payload['branch_id'],
                (int) $payload['package_id'],
                (float) $payload['subtotal_amount'],
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Kode referal tidak valid.',
                422,
                $exception->errors(),
            );
        }

        return $this->responder->success($preview, 'Kode referal berhasil divalidasi.');
    }
}
