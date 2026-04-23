<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkTimeSlotBookableRequest;
use App\Http\Requests\GenerateTimeSlotsRequest;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Services\AdminTimeSlotService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function __construct(
        private readonly AdminTimeSlotService $timeSlotService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage') || $request->user()?->hasRole('owner'), 403);

        $perPage = min((int) $request->integer('per_page', 50), 200);

        $query = TimeSlot::query()
            ->with('branch')
            ->orderByDesc('slot_date')
            ->orderBy('start_time');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('slot_date')) {
            $query->whereDate('slot_date', $request->string('slot_date')->toString());
        }

        if ($request->has('is_bookable')) {
            $query->where('is_bookable', $request->boolean('is_bookable'));
        }

        $slots = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($slots, TimeSlotResource::collection($slots), 'Daftar slot waktu berhasil dimuat.');
    }

    public function store(StoreTimeSlotRequest $request): JsonResponse
    {
        $timeSlot = $this->timeSlotService->create($request->validated());

        return $this->responder->success(new TimeSlotResource($timeSlot->load('branch')), 'Slot waktu berhasil dibuat.', 201);
    }

    public function update(UpdateTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse
    {
        $timeSlot = $this->timeSlotService->update($timeSlot, $request->validated());

        return $this->responder->success(new TimeSlotResource($timeSlot->load('branch')), 'Slot waktu berhasil diperbarui.');
    }

    public function destroy(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage') || $request->user()?->hasRole('owner'), 403);

        $timeSlot->delete();

        return $this->responder->success(null, 'Slot waktu berhasil dihapus.');
    }

    public function bulkBookable(BulkTimeSlotBookableRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $updatedCount = $this->timeSlotService->bulkBookable($payload['slot_ids'], (bool) $payload['is_bookable']);

        return $this->responder->success([
            'updated_count' => $updatedCount,
            'is_bookable' => (bool) $payload['is_bookable'],
        ], 'Status bookable slot berhasil diperbarui.');
    }

    public function generate(GenerateTimeSlotsRequest $request): JsonResponse
    {
        $summary = $this->timeSlotService->generate($request->validated());

        return $this->responder->success([
            'created_count' => $summary['created_count'],
            'skipped_count' => $summary['skipped_count'],
            'branch_id' => $summary['branch_id'],
            'start_date' => $summary['start_date'],
            'end_date' => $summary['end_date'],
        ], 'Generate slot jam selesai.');
    }
}
