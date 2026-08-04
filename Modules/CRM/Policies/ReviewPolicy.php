<?php

namespace Modules\CRM\Policies;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\CRM\Models\Review;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view') || $user->can('reviews.manage');
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->can('reviews.manage')
            && $review->store_id === app(TenantContext::class)->store->id;
    }
}