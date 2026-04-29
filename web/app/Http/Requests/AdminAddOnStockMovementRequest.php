<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAddOnStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movement_type' => ['required', 'string', Rule::in(['in', 'out'])],
            'qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
