<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FulfillOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // See RequestRefundRequest for why this can't check the policy here:
        // $this->route('order') is a raw ID string, not a bound Order model.
        // OrderController::fulfill() calls $this->authorize('fulfill', $order)
        // itself after loading the model.
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}