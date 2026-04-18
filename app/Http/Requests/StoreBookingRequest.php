<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'design_catalog_id' => [
                'nullable',
                'integer',
                Rule::exists('design_catalogs', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'source' => ['nullable', Rule::in(['web', 'walk_in', 'admin'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'add_ons' => ['sometimes', 'array'],
            'add_ons.*.add_on_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('add_ons', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'add_ons.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
