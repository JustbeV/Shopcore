<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 0 is allowed as shorthand for "remove this line" — handled in
            // CartService::updateQuantity().
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }
}
