<?php

namespace Modules\CRM\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Events\CustomerRegistered;
use Modules\CRM\Models\Customer;
use Modules\Tenant\Models\Store;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(CustomerRegistered $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            $customer = Customer::query()->findOrFail($event->customerId);

            // TODO(Notifications module, Phase 6 continued): send the real
            // welcome/verification email.
            Log::info('Welcome email would be sent', ['customer_id' => $customer->id]);
        });
    }
}
