<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminBulkTimeSlotBookableRequest;
use App\Http\Requests\AdminGenerateTimeSlotsRequest;
use App\Http\Requests\AdminStoreTimeSlotRequest;
use App\Http\Requests\AdminUpdateTimeSlotRequest;
use App\Models\TimeSlot;
use App\Services\AdminTimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTimeSlotController extends Controller
{
    public function index(Request $request, AdminTimeSlotService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'time_slots' => $service->rows([
                    'branch_id' => $request->integer('branch_id'),
                    'slot_date' => $request->string('slot_date')->toString(),
                    'is_bookable' => $request->has('is_bookable') ? $request->boolean('is_bookable') : null,
                ]),
            ],
        ]);
    }

    public function store(AdminStoreTimeSlotRequest $request, AdminTimeSlotService $service): JsonResponse
    {
        $service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Time slot created successfully.',
            'data' => [
                'time_slots' => $service->rows(),
            ],
        ], 201);
    }

    public function update(AdminUpdateTimeSlotRequest $request, TimeSlot $timeSlot, AdminTimeSlotService $service): JsonResponse
    {
        $service->update($timeSlot, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Time slot updated successfully.',
            'data' => [
                'time_slots' => $service->rows(),
            ],
        ]);
    }

    public function destroy(TimeSlot $timeSlot, AdminTimeSlotService $service): JsonResponse
    {
        $service->destroy($timeSlot);

        return response()->json([
            'success' => true,
            'message' => 'Time slot deleted successfully.',
            'data' => [
                'time_slots' => $service->rows(),
            ],
        ]);
    }

    public function bulkBookable(AdminBulkTimeSlotBookableRequest $request, AdminTimeSlotService $service): JsonResponse
    {
        $payload = $request->validated();
        $updatedCount = $service->bulkBookable($payload['slot_ids'], (bool) $payload['is_bookable']);

        return response()->json([
            'success' => true,
            'message' => 'Time slot availability updated.',
            'data' => [
                'updated_count' => $updatedCount,
                'time_slots' => $service->rows(),
            ],
        ]);
    }

    public function generate(AdminGenerateTimeSlotsRequest $request, AdminTimeSlotService $service): JsonResponse
    {
        $summary = $service->generate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Time slots generated successfully.',
            'data' => [
                'summary' => $summary,
                'time_slots' => $service->rows([
                    'branch_id' => $summary['branch_id'] ?? null,
                ]),
            ],
        ], 201);
    }
}

