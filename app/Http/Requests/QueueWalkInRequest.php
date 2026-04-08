<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueWalkInRequest extends FormRequest
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
            'queue_date' => ['required', 'date_format:Y-m-d'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
