<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateBlackoutDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $blackoutId = $this->route('blackoutDate')?->id ?? $this->route('blackoutDate');
        $branchId = (int) $this->input('branch_id');

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'blackout_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('blackout_dates', 'blackout_date')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($blackoutId),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_closed' => ['nullable', 'boolean'],
        ];
    }
}

