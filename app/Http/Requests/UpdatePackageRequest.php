<?php

namespace App\Http\Requests;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog.manage') || $this->user()?->hasRole('owner');
    }

    public function rules(): array
    {
        /** @var Package|null $package */
        $package = $this->route('package');

        return [
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'code' => ['required', 'string', 'max:30', Rule::unique('packages', 'code')->ignore($package?->id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'description' => trim((string) $this->input('description')),
        ]);
    }
}
