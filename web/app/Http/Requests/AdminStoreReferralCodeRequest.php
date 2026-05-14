<?php

namespace App\Http\Requests;

use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStoreReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('referral_codes', 'code')],
            'source_name' => ['required', 'string', 'max:120'],
            'source_type' => ['required', Rule::in(ReferralCode::SOURCE_TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in([ReferralCode::DISCOUNT_FIXED, ReferralCode::DISCOUNT_PERCENT])],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'package_id' => ['nullable', 'integer', Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
