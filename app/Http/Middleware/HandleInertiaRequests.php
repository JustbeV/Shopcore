<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $tenant = $request->get('tenant') ?? app('TenantContext')->getTenant();

        return array_merge(parent::share($request), [
            'store' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo_url' => $tenant->logo_url,
                'favicon_url' => $tenant->favicon_url,
                'currency' => $tenant->currency_code ?? 'USD',
                'branding' => [
                    'primary_color' => $tenant->primary_color ?? '#000000',
                    'accent_color' => $tenant->accent_color ?? '#4F46E5',
                    'font_family' => $tenant->font_family ?? 'Inter',
                ],
            ] : null,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ]);
    }
}