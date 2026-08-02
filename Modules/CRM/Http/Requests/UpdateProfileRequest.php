<?php

namespace Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'default_address' => ['sometimes', 'array'],
            'default_address.name' => ['required_with:default_address', 'string'],
            'default_address.line1' => ['required_with:default_address', 'string'],
            'default_address.city' => ['required_with:default_address', 'string'],
            'default_address.postal_code' => ['required_with:default_address', 'string'],
            'default_address.country' => ['required_with:default_address', 'string', 'size:2'],
        ];
    }
}
