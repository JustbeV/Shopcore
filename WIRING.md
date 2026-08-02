# Wiring checklist — base app config needed for Phases 5–6 so far

These aren't module files (they live in the base Laravel skeleton from
Phase 0), so they're not in the zips — but nothing below will work until
they're added. Consolidating everything mentioned across both phases here.

## 1. `config/auth.php`

```php
'guards' => [
    // ...existing 'web' / 'sanctum' guards for Users...
    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],

'providers' => [
    // ...existing 'users' provider...
    'customers' => [
        'driver' => 'eloquent',
        'model' => \Modules\CRM\Models\Customer::class,
    ],
],
```

No `passwords.customers` entry needed — `CustomerPasswordResetService` is a
self-contained, store-scoped replacement for Laravel's built-in broker
(which assumes one global `email` uniqueness domain; ours is unique per
`(store_id, email)`).

## 2. `RouteServiceProvider` — named rate limiters

```php
RateLimiter::for('cart-write', fn ($request) => Limit::perMinute(60)->by($request->ip()));
RateLimiter::for('checkout', fn ($request) => Limit::perMinute(20)->by($request->ip()));
RateLimiter::for('customer-auth', fn ($request) => Limit::perMinute(5)->by($request->input('email').'|'.$request->ip()));
```

(`auth` and `api` limiters were already expected from §11.1/§13 — not
re-listed here.)

## 3. `EventServiceProvider`

```php
protected $listen = [
    // Payments -> Sales (synchronous — see PaymentIntentSucceeded's docblock)
    \Modules\Payments\Events\PaymentIntentSucceeded::class => [
        \Modules\Sales\Listeners\ConfirmOrderPayment::class,
    ],
    \Modules\Payments\Events\PaymentIntentFailed::class => [
        \Modules\Sales\Listeners\ReleaseOrderReservation::class,
    ],

    // Sales (queued)
    \Modules\Sales\Events\OrderPlaced::class => [
        \Modules\Sales\Listeners\SendOrderConfirmation::class,
        \Modules\Sales\Listeners\NotifyMerchantOfNewOrder::class,
        \Modules\Sales\Listeners\UpdateSalesAnalytics::class,
    ],

    // CRM (queued)
    \Modules\CRM\Events\CustomerRegistered::class => [
        \Modules\CRM\Listeners\SendWelcomeEmail::class,
    ],
];
```

## 4. Policies (`AuthServiceProvider` or a Filament/module-specific provider)

```php
protected $policies = [
    \Modules\Sales\Models\Order::class => \Modules\Sales\Policies\OrderPolicy::class,
    \Modules\CRM\Models\Customer::class => \Modules\CRM\Policies\CustomerPolicy::class,
];
```

## 5. Environment variables

```
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```
mapped in `config/services.php`:
```php
'stripe' => [
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

## 6. Container binding for the payment gateway

Stripe-only for now (per your call in Phase 5) — bind the interface directly
rather than building a resolver you don't need yet:

```php
// AppServiceProvider::register()
$this->app->bind(
    \Modules\Payments\Contracts\PaymentGatewayInterface::class,
    \Modules\Payments\Gateways\StripeGateway::class,
);
```

When PayPal is added later, this becomes a small `GatewayResolver` that
picks the implementation based on the store's configured provider instead
of a flat bind.

## 7. `composer.json`

```
composer require stripe/stripe-php
```

## 8. Route registration

Confirm the main `routes/api.php` requires each module's routes file
prefixed under `/api/v1`, and that `Modules/Payments/routes/api.php` is
mounted **outside** the `IdentifyTenant` middleware group (see the comment
at the top of that file — Stripe hits one fixed URL, not a per-tenant
subdomain).
