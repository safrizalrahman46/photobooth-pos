<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('queue.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id'),
            ],
        ];
    }
}
