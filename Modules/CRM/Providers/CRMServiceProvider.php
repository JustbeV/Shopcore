<?php

namespace Modules\CRM\Providers; 

use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Points Laravel to your module's migrations folder
        $this->loadMigrationsFrom(base_path('Modules/CRM/database/migrations'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}