<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('shipping.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'country_code' => ['required', 'string', 'max:3'], // 'ALL' or ISO alpha-2
            'price_cents' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}