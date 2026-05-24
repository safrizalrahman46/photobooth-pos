<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmWalkInRequestPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('transaction.manage') ?? false)
            && ($this->user()?->can('payment.manage') ?? false)
            && ($this->user()?->can('queue.manage') ?? false);
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', Rule::in(['cash'])],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
