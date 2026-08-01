<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FulfillOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fulfill', $this->route('order'));
    }

    public function rules(): array
    {
        return [
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
