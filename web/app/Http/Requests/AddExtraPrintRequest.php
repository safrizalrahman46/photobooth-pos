<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddExtraPrintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('transaction.manage') ?? false)
            && ($this->user()?->can('payment.manage') ?? false);
    }

    public function rules(): array
    {
        return [
            'add_on_id' => [
                'required',
                'integer',
                Rule::exists('add_ons', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'qty' => ['required', 'integer', 'min:1', 'max:100'],
            'payment_method' => ['required', Rule::in(['cash', 'qris', 'transfer', 'card'])],
            'paid_amount' => ['nullable', 'numeric', 'gt:0'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
