<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'add_ons' => ['sometimes', 'array'],
            'add_ons.*.id' => ['nullable', 'integer', 'exists:add_ons,id'],
            'add_ons.*.name' => ['required', 'string', 'max:120'],
            'add_ons.*.description' => ['nullable', 'string', 'max:500'],
            'add_ons.*.price' => ['required', 'numeric', 'min:0'],
            'add_ons.*.max_qty' => ['required', 'integer', 'min:1', 'max:99'],
            'add_ons.*.is_active' => ['nullable', 'boolean'],
            'add_ons.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
