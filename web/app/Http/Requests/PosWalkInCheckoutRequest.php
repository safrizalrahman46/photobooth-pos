<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosWalkInCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transaction.manage') && $this->user()?->can('queue.manage');
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'queue_date' => ['nullable', 'date_format:Y-m-d'],
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*.add_on_id' => [
                'required_with:addons',
                'integer',
                Rule::exists('add_ons', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'addons.*.qty' => ['required_with:addons', 'integer', 'min:1', 'max:99'],
            'payment_method' => ['required', Rule::in(array_map(fn (PaymentMethod $method): string => $method->value, PaymentMethod::cases()))],
            'paid_amount' => ['nullable', 'numeric', 'gt:0'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
