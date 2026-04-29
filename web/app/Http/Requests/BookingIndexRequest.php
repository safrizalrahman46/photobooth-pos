<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingIndexRequest extends FormRequest
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
            'status' => [
                'nullable',
                Rule::in(array_map(
                    static fn (BookingStatus $status): string => $status->value,
                    BookingStatus::cases(),
                )),
            ],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
