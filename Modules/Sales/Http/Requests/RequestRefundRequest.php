<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Deferred to the controller, which loads the Order model before
        // calling $this->authorize('request', $order) — route params here
        // are raw ID strings, not bound model instances, so a policy check
        // against $this->route('order') would silently pass a string
        // instead of the Order the policy actually needs.
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}