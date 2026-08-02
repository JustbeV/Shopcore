<?php

namespace Modules\CRM\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = app(TenantContext::class)->store->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')
                    ->where(fn ($query) => $query->where('store_id', $storeId)
                        ->whereNull('deleted_at')
                        ->whereNotNull('password')),
            ],
            // Note: a pre-existing GUEST customer (created during a prior
            // guest checkout, password still null) with this email is
            // handled separately in CustomerAuthService::register() — the
            // unique rule above only blocks colliding with an account that
            // already HAS a password.
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
