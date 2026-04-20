<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function __construct(
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
        $payload = $request->validated();

        if ($this->hasOverlap(
            (int) $payload['branch_id'],
            (string) $payload['slot_date'],
            (string) $payload['start_time'],
            (string) $payload['end_time']
        )) {
            return $this->responder->error('Rentang waktu slot bertabrakan dengan slot lain pada tanggal yang sama.', 422);
        }

        $timeSlot = TimeSlot::query()->create([
            'branch_id' => $payload['branch_id'],
            'slot_date' => $payload['slot_date'],
            'start_time' => $payload['start_time'],
            'end_time' => $payload['end_time'],
            'capacity' => $payload['capacity'],
            'is_bookable' => $payload['is_bookable'] ?? true,
        ]);

        return $this->responder->success(new TimeSlotResource($timeSlot->load('branch')), 'Slot waktu berhasil dibuat.', 201);
    }

    public function update(UpdateTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse
    {
        $payload = $request->validated();

        if ($this->hasOverlap(
            (int) $payload['branch_id'],
            (string) $payload['slot_date'],
            (string) $payload['start_time'],
            (string) $payload['end_time'],
            $timeSlot->id,
        )) {
            return $this->responder->error('Rentang waktu slot bertabrakan dengan slot lain pada tanggal yang sama.', 422);
        }

        $timeSlot->fill([
            'branch_id' => $payload['branch_id'],
            'slot_date' => $payload['slot_date'],
            'start_time' => $payload['start_time'],
            'end_time' => $payload['end_time'],
            'capacity' => $payload['capacity'],
            'is_bookable' => $payload['is_bookable'] ?? true,
        ]);

        $timeSlot->save();

        return $this->responder->success(new TimeSlotResource($timeSlot->load('branch')), 'Slot waktu berhasil diperbarui.');
    }

    private function hasOverlap(
        int $branchId,
        string $slotDate,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null,
    ): bool {
        $query = TimeSlot::query()
            ->where('branch_id', $branchId)
            ->whereDate('slot_date', $slotDate)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
