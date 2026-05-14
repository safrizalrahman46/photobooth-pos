<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referral_code' => ['required', 'string', 'max:40'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
