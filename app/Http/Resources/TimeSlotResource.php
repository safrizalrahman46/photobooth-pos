<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'slot_date' => $this->slot_date?->toDateString(),
            'start_time' => (string) $this->start_time,
            'end_time' => (string) $this->end_time,
            'capacity' => (int) $this->capacity,
            'is_bookable' => (bool) $this->is_bookable,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
