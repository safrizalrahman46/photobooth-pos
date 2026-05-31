<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashierSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'business_date' => $this->business_date?->toDateString(),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by' => $this->closed_by,
            'opening_cash' => (float) $this->opening_cash,
            'closing_cash' => $this->closing_cash !== null ? (float) $this->closing_cash : null,
            'is_late_close' => (bool) $this->is_late_close,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'settlement_id' => $this->whenLoaded('settlement', fn () => $this->settlement?->id),
        ];
    }
}
