<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkTimeSlotBookableRequest;
use App\Http\Requests\GenerateTimeSlotsRequest;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Support\ApiResponder;
use Carbon\Carbon;
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

    public function destroy(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage') || $request->user()?->hasRole('owner'), 403);

        $timeSlot->delete();

        return $this->responder->success(null, 'Slot waktu berhasil dihapus.');
    }

    public function bulkBookable(BulkTimeSlotBookableRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $updatedCount = TimeSlot::query()
            ->whereIn('id', $payload['slot_ids'])
            ->update([
                'is_bookable' => (bool) $payload['is_bookable'],
                'updated_at' => now(),
            ]);

        return $this->responder->success([
            'updated_count' => $updatedCount,
            'is_bookable' => (bool) $payload['is_bookable'],
        ], 'Status bookable slot berhasil diperbarui.');
    }

    public function generate(GenerateTimeSlotsRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $startDate = Carbon::parse($payload['start_date'])->startOfDay();
        $endDate = Carbon::parse($payload['end_date'])->startOfDay();
        $interval = (int) $payload['interval_minutes'];
        $branchId = (int) $payload['branch_id'];
        $capacity = (int) $payload['capacity'];
        $isBookable = (bool) ($payload['is_bookable'] ?? true);

        $createdCount = 0;
        $skippedCount = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $cursor = Carbon::parse($dateString.' '.$payload['day_start_time']);
            $dayEnd = Carbon::parse($dateString.' '.$payload['day_end_time']);

            while ($cursor->copy()->addMinutes($interval)->lte($dayEnd)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($interval);
                $startTime = $slotStart->format('H:i:s');
                $endTime = $slotEnd->format('H:i:s');

                if ($this->hasOverlap($branchId, $dateString, $startTime, $endTime)) {
                    $skippedCount++;
                    $cursor->addMinutes($interval);
                    continue;
                }

                TimeSlot::query()->create([
                    'branch_id' => $branchId,
                    'slot_date' => $dateString,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'capacity' => $capacity,
                    'is_bookable' => $isBookable,
                ]);

                $createdCount++;
                $cursor->addMinutes($interval);
            }
        }

        return $this->responder->success([
            'created_count' => $createdCount,
            'skipped_count' => $skippedCount,
            'branch_id' => $branchId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ], 'Generate slot jam selesai.');
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
