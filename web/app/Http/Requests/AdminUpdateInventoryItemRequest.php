<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $inventoryItemId = (int) ($this->route('inventoryItem')?->id ?? 0);

        return [
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('inventory_items', 'code')->ignore($inventoryItemId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:20'],
            'available_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
