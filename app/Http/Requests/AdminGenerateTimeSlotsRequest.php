<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminGenerateTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'day_start_time' => ['required', 'date_format:H:i:s'],
            'day_end_time' => ['required', 'date_format:H:i:s', 'after:day_start_time'],
            'interval_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'is_bookable' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => trim((string) $this->input('start_date')),
            'end_date' => trim((string) $this->input('end_date')),
            'day_start_time' => $this->normalizeTime((string) $this->input('day_start_time')),
            'day_end_time' => $this->normalizeTime((string) $this->input('day_end_time')),
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

