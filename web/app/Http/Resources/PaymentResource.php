<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'payment_code' => $this->payment_code,
            'method' => $this->method?->value ?? $this->method,
            'amount' => (float) $this->amount,
            'reference_no' => $this->reference_no,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cashier_id' => $this->cashier_id,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
