<?php

declare(strict_types=1);

namespace Modules\Platform\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Platform\app\Models\MerchantApplication;

/**
 * Validates a prospective merchant's application submission.
 *
 * Deliberately validates only what's needed to *create* the
 * application record (§7.2 ERD: business_name, business_type,
 * metadata). It does not create the applicant's User account —
 * that's Identity module territory. This request assumes the
 * applicant is already authenticated (see controller) and is only
 * submitting their business details.
 */
final class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may submit an application, provided
        // they don't already have one pending. The "one pending
        // application per user" rule is enforced below in
        // withValidator() rather than a Policy, since there's no
        // MerchantApplication instance to authorize against yet —
        // this is a *creation* precondition, not an authorization
        // decision over an existing resource.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:2', 'max:255'],

            'business_type' => [
                'required',
                'string',
                Rule::in([
                    'sole_proprietorship',
                    'partnership',
                    'limited_company',
                    'corporation',
                    'non_profit',
                    'other',
                ]),
            ],

            // Free-form business details captured as metadata (§7.2:
            // merchant_applications.metadata jsonb). Validated as a
            // structured sub-set rather than an open bag, so garbage
            // input can't silently land in the JSON column.
            'metadata' => ['required', 'array'],
            'metadata.contact_name' => ['required', 'string', 'max:255'],
            'metadata.contact_phone' => ['required', 'string', 'max:32'],
            'metadata.proposed_store_name' => ['required', 'string', 'max:255'],
            'metadata.category' => ['required', 'string', 'max:100'],
            'metadata.country' => ['required', 'string', 'size:2'],
            'metadata.website' => ['nullable', 'url', 'max:255'],
            'metadata.expected_monthly_revenue' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasPending = MerchantApplication::query()
                ->where('user_id', $this->user()->id)
                ->whereIn('status', [
                    MerchantApplication::STATUS_SUBMITTED,
                    MerchantApplication::STATUS_UNDER_REVIEW,
                    MerchantApplication::STATUS_INFO_REQUESTED,
                ])
                ->exists();

            if ($hasPending) {
                $validator->errors()->add(
                    'business_name',
                    'You already have a merchant application under review. Please wait for a decision before submitting another.'
                );
            }
        });
    }

    /**
     * Strip anything not explicitly validated before it ever reaches
     * the controller/model — defense in depth alongside the model's
     * $fillable allow-list (architecture doc §13).
     */
    public function validatedApplicationData(): array
    {
        return $this->safe()->only([
            'business_name',
            'business_type',
            'metadata',
        ]);
    }
}