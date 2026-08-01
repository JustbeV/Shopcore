<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InitiateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Only required for guest checkout — an authenticated customer's
            // email is already known. See CheckoutController::resolveCustomer().
            'customer_email' => [
                Rule::requiredIf(fn () => ! Auth::guard('customer')->check()),
                'email',
            ],
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string'],
            'shipping_address.line1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.postal_code' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'billing_address' => ['sometimes', 'array'],
            'billing_address.name' => ['required_with:billing_address', 'string'],
            'billing_address.line1' => ['required_with:billing_address', 'string'],
            'billing_address.city' => ['required_with:billing_address', 'string'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'size:2'],
        ];
    }

    /**
     * Billing defaults to shipping when omitted — the common case.
     */
    public function billingAddress(): array
    {
        return $this->input('billing_address') ?? $this->input('shipping_address');
    }
}
