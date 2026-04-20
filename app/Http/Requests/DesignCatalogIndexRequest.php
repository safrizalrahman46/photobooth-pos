<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesignCatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
        ];
    }
}
