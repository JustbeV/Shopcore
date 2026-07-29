<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $tenantContext = app(TenantContext::class);
        $tenant = $tenantContext->has() ? $tenantContext->get() : null;

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],

            'store' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug ?? null,
                'logo_url' => $tenant->logo_url ?? null,
                'favicon_url' => $tenant->favicon_url ?? null,
                'currency' => $tenant->currency_code ?? 'USD',
                'branding' => [
                    'primary_color' => $tenant->primary_color ?? $tenant->settings['branding']['primary_color'] ?? '#000000',
                    'accent_color' => $tenant->accent_color ?? $tenant->settings['branding']['accent_color'] ?? '#4F46E5',
                    'font_family' => $tenant->font_family ?? $tenant->settings['branding']['font_family'] ?? 'Inter',
                ],
            ] : null,

            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }
}