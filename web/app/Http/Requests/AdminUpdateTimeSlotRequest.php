<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'slot_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'is_bookable' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slot_date' => trim((string) $this->input('slot_date')),
            'start_time' => $this->normalizeTime((string) $this->input('start_time')),
            'end_time' => $this->normalizeTime((string) $this->input('end_time')),
        ]);
    }

    private function normalizeTime(string $value): string
    {
        $trimmed = trim($value);

        if (preg_match('/^\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed.':00';
        }

        return $trimmed;
    }
}

