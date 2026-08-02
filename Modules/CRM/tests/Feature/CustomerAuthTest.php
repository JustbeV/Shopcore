<?php

use Modules\Catalog\Models\ProductVariant;
use Modules\CRM\Models\Customer;
use Modules\CRM\Services\CustomerPasswordResetService;
use Modules\Sales\Models\Cart;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);
});

it('registers a new customer with a valid password', function () {
    $response = $this->postJson('/api/v1/store/auth/register', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Str0ngPassword!',
        'password_confirmation' => 'Str0ngPassword!',
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'ada@example.com');
    expect(Customer::query()->where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('rejects registration with a weak password', function () {
    $response = $this->postJson('/api/v1/store/auth/register', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('password');
});

it('lets registration claim an existing guest account instead of colliding on email', function () {
    $guest = Customer::factory()->guest()->create(['store_id' => $this->store->id, 'email' => 'ada@example.com']);

    $response = $this->postJson('/api/v1/store/auth/register', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Str0ngPassword!',
        'password_confirmation' => 'Str0ngPassword!',
    ]);

    $response->assertCreated()->assertJsonPath('data.id', $guest->id);
    expect(Customer::query()->where('store_id', $this->store->id)->where('email', 'ada@example.com')->count())->toBe(1);
});

it('merges a guest cart into the customer cart on login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'ada@example.com']);
    $variant = ProductVariant::factory()->create(['price_cents' => 1500, 'track_inventory' => false]);

    $cartToken = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->headers->get('X-Cart-Token');

    $response = $this->postJson('/api/v1/store/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'Password1234!',
    ], ['X-Cart-Token' => $cartToken]);

    $response->assertOk();

    $customerCart = Cart::query()->where('customer_id', $customer->id)->first();
    expect($customerCart)->not->toBeNull()
        ->and($customerCart->totalQuantity())->toBe(1);
});

it('rejects login with a generic error on bad credentials', function () {
    Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'ada@example.com']);

    $response = $this->postJson('/api/v1/store/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
});

it('updates the authenticated customer\'s own profile', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $this->actingAs($customer, 'customer');

    $response = $this->putJson('/api/v1/store/account/profile', ['first_name' => 'Updated']);

    $response->assertOk()->assertJsonPath('data.first_name', 'Updated');
});

it('completes a full password reset flow', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'ada@example.com']);

    $this->postJson('/api/v1/store/auth/forgot-password', ['email' => 'ada@example.com'])->assertOk();

    // The token isn't exposed over HTTP (see the service's docblock) — pull
    // it directly to exercise the reset step, same as a real test would
    // parse it out of a captured email (Mailpit) in an E2E run.
    $token = app(CustomerPasswordResetService::class)->requestReset('ada@example.com');

    $response = $this->postJson('/api/v1/store/auth/reset-password', [
        'email' => 'ada@example.com',
        'token' => $token,
        'password' => 'NewStr0ngPassword!',
        'password_confirmation' => 'NewStr0ngPassword!',
    ]);

    $response->assertOk();
    expect(\Illuminate\Support\Facades\Hash::check('NewStr0ngPassword!', $customer->fresh()->password))->toBeTrue();
});
