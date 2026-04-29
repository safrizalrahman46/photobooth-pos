<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') || $this->user()?->hasRole('owner');
    }

    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch?->id)],
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
