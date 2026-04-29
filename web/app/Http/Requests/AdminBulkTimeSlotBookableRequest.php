<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBulkTimeSlotBookableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_ids' => ['required', 'array', 'min:1', 'max:500'],
            'slot_ids.*' => ['required', 'integer', Rule::exists('time_slots', 'id')],
            'is_bookable' => ['required', 'boolean'],
        ];
    }
}

