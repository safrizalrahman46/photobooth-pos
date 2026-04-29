<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStorePrinterSettingRequest;
use App\Http\Requests\AdminUpdatePrinterSettingRequest;
use App\Models\PrinterSetting;
use App\Services\AdminPrinterSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPrinterSettingController extends Controller
{
    public function index(Request $request, AdminPrinterSettingService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'printer_settings' => $service->rows([
                    'branch_id' => $request->integer('branch_id'),
                    'printer_type' => $request->string('printer_type')->toString(),
                    'include_inactive' => $request->boolean('include_inactive'),
                ]),
            ],
        ]);
    }

    public function store(AdminStorePrinterSettingRequest $request, AdminPrinterSettingService $service): JsonResponse
    {
        $service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Printer setting created successfully.',
            'data' => [
                'printer_settings' => $service->rows(['include_inactive' => true]),
            ],
        ], 201);
    }

    public function update(
        AdminUpdatePrinterSettingRequest $request,
        PrinterSetting $printerSetting,
        AdminPrinterSettingService $service,
    ): JsonResponse {
        $service->update($printerSetting, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Printer setting updated successfully.',
            'data' => [
                'printer_settings' => $service->rows(['include_inactive' => true]),
            ],
        ]);
    }

    public function setDefault(PrinterSetting $printerSetting, AdminPrinterSettingService $service): JsonResponse
    {
        $service->setDefault($printerSetting);

        return response()->json([
            'success' => true,
            'message' => 'Default printer updated.',
            'data' => [
                'printer_settings' => $service->rows(['include_inactive' => true]),
            ],
        ]);
    }

    public function destroy(PrinterSetting $printerSetting, AdminPrinterSettingService $service): JsonResponse
    {
        $service->destroy($printerSetting);

        return response()->json([
            'success' => true,
            'message' => 'Printer setting deleted successfully.',
            'data' => [
                'printer_settings' => $service->rows(['include_inactive' => true]),
            ],
        ]);
    }
}

