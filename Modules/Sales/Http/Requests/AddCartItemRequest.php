<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cart mutation is open to guests and authenticated customers alike;
        // ownership of the *cart itself* is enforced by CartController's
        // resolution logic (guest token / customer_id), not here.
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => [
                'required',
                'string',
                Rule::exists('product_variants', 'id')->where(
                    fn ($query) => $query->whereNull('deleted_at')
                ),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
