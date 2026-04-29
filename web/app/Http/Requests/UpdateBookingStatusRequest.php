<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('booking.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'pending',
                'confirmed',
                'paid',
                'checked_in',
                'in_queue',
                'in_session',
                'done',
                'cancelled',
            ])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
