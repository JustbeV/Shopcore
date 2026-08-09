<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checked in the controller, same reasoning as FulfillOrderRequest
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}