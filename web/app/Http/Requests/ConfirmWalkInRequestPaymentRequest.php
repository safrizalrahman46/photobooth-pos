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

    protected function prepareForValidation(): void
    {
        if ($this->has('payment_method')) {
            $this->merge([
                'payment_method' => strtolower((string) $this->input('payment_method')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', Rule::in(['cash', 'qris'])],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'social_media_consent' => ['nullable', 'boolean'],
        ];
    }
}
