<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalkInRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('transaction.manage') ?? false)
            && ($this->user()?->can('payment.manage') ?? false);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'string', 'max:120'],
            'customer_phone' => ['sometimes', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'package_id' => ['sometimes', 'integer', 'exists:packages,id'],
            'addons' => ['sometimes', 'array', 'max:20'],
            'addons.*.add_on_id' => ['required_with:addons', 'integer', 'exists:add_ons,id'],
            'addons.*.qty' => ['required_with:addons', 'integer', 'min:1', 'max:99'],
        ];
    }
}
