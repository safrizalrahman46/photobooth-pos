<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')],
            'queue_ticket_id' => ['nullable', 'integer', Rule::exists('queue_tickets', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', 'max:30'],
            'items.*.item_ref_id' => ['nullable', 'integer'],
            'items.*.item_name' => ['required', 'string', 'max:120'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
