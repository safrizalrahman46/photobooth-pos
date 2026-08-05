<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payment.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['cash', 'qris', 'transfer', 'card'])],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
