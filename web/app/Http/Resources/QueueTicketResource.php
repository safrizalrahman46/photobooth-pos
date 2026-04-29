<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_code' => $this->queue_code,
            'branch_id' => $this->branch_id,
            'queue_date' => $this->queue_date?->toDateString(),
            'queue_number' => $this->queue_number,
            'source_type' => $this->source_type?->value ?? $this->source_type,
            'booking_id' => $this->booking_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status?->value ?? $this->status,
            'priority' => $this->priority,
            'called_at' => $this->called_at?->toIso8601String(),
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'skipped_at' => $this->skipped_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'booking' => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
