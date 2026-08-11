<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\Tenancy\TenantContext;

class SaveCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('coupons.manage');
    }

    public function rules(): array
    {
        $couponId = $this->route('couponId');
        $storeId = app(TenantContext::class)->store->id;

        return [
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('coupons', 'code')
                    ->where(fn ($q) => $q->where('store_id', $storeId))
                    ->ignore($couponId),
            ],
            'type' => ['required', Rule::in(['percentage', 'fixed', 'free_shipping'])],
            'value' => ['required_unless:type,free_shipping', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}