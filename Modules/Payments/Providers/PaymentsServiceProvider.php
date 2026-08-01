<?php

namespace Modules\Payments\Providers; 

use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Points Laravel to your module's migrations folder
        $this->loadMigrationsFrom(base_path('Modules/Payments/database/migrations'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}