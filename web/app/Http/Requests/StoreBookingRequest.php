<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JsonException;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
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
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'payment_type' => ['nullable', Rule::in(['full', 'dp50'])],
            'source' => ['nullable', Rule::in(['web', 'walk_in', 'admin'])],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*.id' => ['required_with:addons', 'string', 'max:80'],
            'addons.*.label' => ['required_with:addons', 'string', 'max:150'],
            'addons.*.qty' => ['required_with:addons', 'integer', 'min:1', 'max:99'],
            'addons.*.price' => ['required_with:addons', 'numeric', 'min:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->routeIs('booking.store')) {
            $rules['payment_type'] = ['required', Rule::in(['full', 'dp50'])];
            $rules['transfer_proof'] = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $decodedAddons = $this->decodeAddonsPayload();

        if ($decodedAddons !== null) {
            $this->merge([
                'addons' => $decodedAddons,
            ]);
        }
    }

    private function decodeAddonsPayload(): ?array
    {
        $payload = $this->input('addons_payload');

        if (! is_string($payload) || trim($payload) === '') {
            return null;
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        return collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'qty' => (int) ($item['qty'] ?? 0),
                    'price' => (float) ($item['price'] ?? 0),
                ];
            })
            ->filter(fn (array $item) => $item['id'] !== '' && $item['label'] !== '' && $item['qty'] > 0)
            ->values()
            ->all();
    }
}
