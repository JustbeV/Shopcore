<?php

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\ProductVariant;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\Events\PaymentIntentFailed;
use Modules\Payments\Events\PaymentIntentSucceeded;
use Modules\Payments\Models\Payment;
use Modules\Payments\Testing\FakeGateway;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\CheckoutService;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(PaymentGatewayInterface::class, FakeGateway::class);
    FakeGateway::reset();

    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->variant = ProductVariant::factory()->create(['price_cents' => 4000, 'track_inventory' => true]);
    $this->inventory = InventoryItem::factory()->create([
        'variant_id' => $this->variant->id,
        'store_id' => $this->store->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);

    $this->address = [
        'name' => 'Ada Lovelace', 'line1' => '1 Analytical Engine Way',
        'city' => 'London', 'postal_code' => 'SW1A 1AA', 'country' => 'GB',
    ];
});

function addToCart(): string
{
    $response = test()->postJson('/api/v1/store/cart/items', [
        'variant_id' => test()->variant->id,
        'quantity' => 2,
    ]);

    return $response->headers->get('X-Cart-Token');
}

it('creates a pending order and reserves stock on checkout initiation', function () {
    $token = addToCart();

    $response = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['X-Cart-Token' => $token]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total_cents', 8000);

    expect($response->json('meta.client_secret'))->not->toBeNull();

    $this->inventory->refresh();
    expect($this->inventory->quantity_reserved)->toBe(2);

    // Cart was cleared after a successful checkout.
    $cartResponse = $this->getJson('/api/v1/store/cart', ['X-Cart-Token' => $token]);
    $cartResponse->assertJsonPath('data.total_quantity', 0);
});

it('rejects checkout when stock was depleted after the item was added to the cart', function () {
    $token = addToCart(); // adds 2, well within the 5 on hand at add-time

    // Simulate another customer buying the remaining stock between "add to
    // cart" and "checkout" — this is exactly the race InventoryService's
    // atomic UPDATE (not read-then-write) is meant to catch.
    $this->inventory->update(['quantity_on_hand' => 1]);

    $response = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['X-Cart-Token' => $token]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'OUT_OF_STOCK');

    expect(Order::query()->count())->toBe(0);
    $this->inventory->refresh();
    expect($this->inventory->quantity_reserved)->toBe(0); // rolled back, nothing left dangling
});

it('replays the same order and client_secret for a repeated Idempotency-Key', function () {
    $token = addToCart();
    $key = (string) \Illuminate\Support\Str::uuid();

    $first = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['X-Cart-Token' => $token, 'Idempotency-Key' => $key]);

    $second = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['Idempotency-Key' => $key]); // no cart token needed — order already exists

    $second->assertCreated();
    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($second->json('meta.client_secret'))->toBe($first->json('meta.client_secret'))
        ->and(Order::query()->count())->toBe(1);
});

it('marks the order paid and commits inventory when the payment webhook succeeds', function () {
    Event::fake([OrderPlaced::class]);

    $token = addToCart();
    $checkoutResponse = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['X-Cart-Token' => $token]);

    $orderId = $checkoutResponse->json('data.id');
    $payment = Payment::withoutGlobalScopes()->where('order_id', $orderId)->firstOrFail();

    app(CheckoutService::class)->confirmPayment($payment->provider_reference);

    $order = Order::withoutGlobalScopes()->findOrFail($orderId);
    expect($order->status)->toBe('paid');

    $this->inventory->refresh();
    expect($this->inventory->quantity_on_hand)->toBe(3) // 5 - 2 committed
        ->and($this->inventory->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPlaced::class, fn ($e) => $e->orderId === $orderId);
});

it('cancels the order and releases the reservation when the payment webhook fails', function () {
    $token = addToCart();
    $checkoutResponse = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => $this->address,
    ], ['X-Cart-Token' => $token]);

    $orderId = $checkoutResponse->json('data.id');
    $payment = Payment::withoutGlobalScopes()->where('order_id', $orderId)->firstOrFail();

    app(CheckoutService::class)->failPayment($payment->provider_reference, 'card_declined');

    $order = Order::withoutGlobalScopes()->findOrFail($orderId);
    expect($order->status)->toBe('cancelled');

    $this->inventory->refresh();
    expect($this->inventory->quantity_reserved)->toBe(0)
        ->and($this->inventory->quantity_on_hand)->toBe(5); // untouched — never committed
});

it('ignores a webhook for an unknown payment reference without error', function () {
    app(CheckoutService::class)->confirmPayment('pi_does_not_exist');
})->throwsNoExceptions();
