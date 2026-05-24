<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalkInRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'request_code' => (string) $this->request_code,
            'branch_id' => (int) $this->branch_id,
            'branch_name' => (string) ($this->branch?->name ?? ''),
            'package_id' => (int) $this->package_id,
            'package_name' => (string) $this->package_name,
            'package_price' => (float) $this->package_price,
            'customer_name' => (string) $this->customer_name,
            'customer_phone' => (string) $this->customer_phone,
            'add_ons' => collect($this->add_ons_json ?? [])->values()->all(),
            'subtotal_amount' => (float) $this->subtotal_amount,
            'total_amount' => (float) $this->total_amount,
            'status' => (string) $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'confirmed_by' => $this->confirmed_by ? (int) $this->confirmed_by : null,
            'transaction_id' => $this->transaction_id ? (int) $this->transaction_id : null,
            'queue_ticket_id' => $this->queue_ticket_id ? (int) $this->queue_ticket_id : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'queue_ticket' => new QueueTicketResource($this->whenLoaded('queueTicket')),
        ];
    }
}
