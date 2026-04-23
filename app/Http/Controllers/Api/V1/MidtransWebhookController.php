<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Midtrans webhook is disabled. Payment is handled via manual BRI QR transfer proof.',
            'data' => [],
        ], 404);
    }
}
