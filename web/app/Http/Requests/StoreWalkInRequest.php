<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInRequest extends FormRequest
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
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*.add_on_id' => [
                'required_with:addons',
                'integer',
                Rule::exists('add_ons', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'addons.*.qty' => ['required_with:addons', 'integer', 'min:1', 'max:99'],
            'submission_key' => ['nullable', 'string', 'max:100'],
            'terms_accepted' => ['accepted'],
        ];
    }
}
