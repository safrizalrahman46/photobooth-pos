<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') || $this->user()?->hasRole('owner');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'timezone'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'timezone' => trim((string) $this->input('timezone', 'Asia/Jakarta')),
            'phone' => trim((string) $this->input('phone')),
            'address' => trim((string) $this->input('address')),
        ]);
    }
}
