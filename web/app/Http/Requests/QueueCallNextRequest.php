<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueCallNextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'queue_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
