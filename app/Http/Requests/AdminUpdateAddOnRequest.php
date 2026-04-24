<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $addOnId = (int) ($this->route('addOn')?->id ?? 0);

        return [
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('add_ons', 'code')->ignore($addOnId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_qty' => ['required', 'integer', 'min:1', 'max:99'],
            'is_physical' => ['nullable', 'boolean'],
            'available_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
