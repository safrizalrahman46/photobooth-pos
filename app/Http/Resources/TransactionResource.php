<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'branch_id' => $this->branch_id,
            'booking_id' => $this->booking_id,
            'queue_ticket_id' => $this->queue_ticket_id,
            'cashier_id' => $this->cashier_id,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'change_amount' => (float) $this->change_amount,
            'status' => $this->status?->value ?? $this->status,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'item_ref_id' => $item->item_ref_id,
                'item_name' => $item->item_name,
                'qty' => (float) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
