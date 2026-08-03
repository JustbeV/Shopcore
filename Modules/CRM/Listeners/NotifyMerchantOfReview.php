<?php

namespace Modules\CRM\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Events\ReviewSubmitted;
use Modules\Tenant\Models\Store;

class NotifyMerchantOfReview implements ShouldQueue
{
    public function handle(ReviewSubmitted $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            // TODO(Notifications module): notify staff with 'reviews.manage'.
            Log::info('Merchant new-review notification would be sent', ['review_id' => $event->reviewId]);
        });
    }
}