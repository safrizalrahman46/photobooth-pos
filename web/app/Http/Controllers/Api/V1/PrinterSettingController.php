<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrinterSettingResource;
use App\Models\PrinterSetting;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrinterSettingController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage') || $request->user()?->can('transaction.manage'), 403);

        $query = PrinterSetting::query()->orderByDesc('is_default')->orderBy('device_name');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        $printers = $query->get();

        return $this->responder->success(PrinterSettingResource::collection($printers), 'Pengaturan printer berhasil dimuat.');
    }
}
