<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'branch_id' => $this->branch_id,
            'package_id' => $this->package_id,
            'design_catalog_id' => $this->design_catalog_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'booking_date' => $this->booking_date?->toDateString(),
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'status' => $this->status?->value ?? $this->status,
            'source' => $this->source?->value ?? $this->source,
            'payment_type' => $this->payment_type,
            'payment_gateway' => $this->payment_gateway,
            'payment_reference' => $this->payment_reference,
            'payment_url' => $this->payment_url,
            'payment_expires_at' => $this->payment_expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'addons' => $this->addons ?? [],
            'addon_total' => (float) $this->addon_total,
            'total_amount' => (float) $this->total_amount,
            'deposit_amount' => (float) $this->deposit_amount,
            'paid_amount' => (float) $this->paid_amount,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'package' => new PackageResource($this->whenLoaded('package')),
            'design_catalog' => new DesignCatalogResource($this->whenLoaded('designCatalog')),
        ];
    }
}
