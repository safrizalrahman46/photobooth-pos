<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'waiting',
                'called',
                'checked_in',
                'in_session',
                'finished',
                'skipped',
                'cancelled',
            ])],
        ];
    }
}
