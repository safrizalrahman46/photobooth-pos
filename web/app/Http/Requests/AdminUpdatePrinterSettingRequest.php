<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdatePrinterSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');
        $settingId = $this->route('printerSetting')?->id ?? $this->route('printerSetting');

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'device_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('printer_settings', 'device_name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($settingId),
            ],
            'printer_type' => ['required', Rule::in(['thermal', 'inkjet', 'laser'])],
            'connection' => ['nullable', 'array'],
            'paper_width_mm' => ['required', 'integer', 'min:58', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

