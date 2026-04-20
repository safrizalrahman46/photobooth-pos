<?php

namespace App\Http\Requests;

use App\Enums\QueueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'queue_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => [
                'nullable',
                Rule::in(array_map(
                    static fn (QueueStatus $status): string => $status->value,
                    QueueStatus::cases(),
                )),
            ],
        ];
    }
}
