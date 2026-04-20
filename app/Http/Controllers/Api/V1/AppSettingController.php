<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AppSettingService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppSettingController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settingService,
        private readonly ApiResponder $responder,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizeSettings($request);

        return $this->responder->success($this->settingService->publicSettings(), 'Pengaturan aplikasi berhasil dimuat.');
    }

    public function update(Request $request, string $group): JsonResponse
    {
        $this->authorizeSettings($request);

        if (! in_array($group, ['general', 'booking', 'payment'], true)) {
            return $this->responder->error('Grup pengaturan tidak dikenal.', 404);
        }

        $payload = $request->validate([
            'value' => ['required', 'array'],
        ]);

        $stored = $this->settingService->set($group, $payload['value'], (int) $request->user()->id);

        return $this->responder->success([
            'key' => $stored->key,
            'value' => $stored->value,
            'updated_at' => $stored->updated_at?->toIso8601String(),
        ], 'Pengaturan aplikasi berhasil diperbarui.');
    }

    private function authorizeSettings(Request $request): void
    {
        abort_unless($request->user()?->can('settings.manage'), Response::HTTP_FORBIDDEN);
    }
}
