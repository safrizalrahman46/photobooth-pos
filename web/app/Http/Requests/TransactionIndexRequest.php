<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends FormRequest
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
                    static fn (TransactionStatus $status): string => $status->value,
                    TransactionStatus::cases(),
                )),
            ],
        ];
    }
}
