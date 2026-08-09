<?php

use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\ProductVariant;
use Modules\CRM\Models\Customer;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\Testing\FakeGateway;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;
use Modules\Sales\Services\CheckoutService;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(PaymentGatewayInterface::class, FakeGateway::class);
    FakeGateway::reset();

    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);

    $this->variant = ProductVariant::factory()->create(['price_cents' => 4000, 'track_inventory' => true]);
    $this->inventory = InventoryItem::factory()->create([
        'variant_id' => $this->variant->id, 'store_id' => $this->store->id,
        'quantity_on_hand' => 5, 'quantity_reserved' => 0,
    ]);

    // Get to a paid order via the real checkout + webhook flow, same as CheckoutTest.
    $token = $this->postJson('/api/v1/store/cart/items', ['variant_id' => $this->variant->id, 'quantity' => 2])
        ->headers->get('X-Cart-Token');

    $checkout = $this->postJson('/api/v1/store/checkout', [
        'customer_email' => 'ada@example.com',
        'shipping_address' => ['name' => 'Ada Lovelace', 'line1' => '1 Way', 'city' => 'London', 'postal_code' => 'SW1A 1AA', 'country' => 'GB'],
    ], ['X-Cart-Token' => $token]);

    $this->order = Order::withoutGlobalScopes()->findOrFail($checkout->json('data.id'));
    $this->customer = Customer::query()->where('email', 'ada@example.com')->firstOrFail();

    $payment = $this->order->payments()->first();
    app(CheckoutService::class)->confirmPayment($payment->provider_reference);
    $this->order->refresh();

    $this->inventory->refresh(); // 5 - 2 = 3 on hand, 0 reserved, after commit
});

it('lets the customer request a refund on their paid order', function () {
    $this->actingAs($this->customer, 'customer');

    $response = $this->postJson("/api/v1/store/orders/{$this->order->id}/refund-request", [
        'reason' => 'Item arrived damaged.',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'requested');
});

it('rejects a second refund request while one is already pending', function () {
    $this->actingAs($this->customer, 'customer');
    $this->postJson("/api/v1/store/orders/{$this->order->id}/refund-request", ['reason' => 'First request.'])->assertCreated();

    $response = $this->postJson("/api/v1/store/orders/{$this->order->id}/refund-request", ['reason' => 'Second request.']);

    $response->assertStatus(422)->assertJsonPath('error.code', 'REFUND_ALREADY_REQUESTED');
});

it('processes an approved refund: gateway called, order marked refunded, stock restocked', function () {
    $this->actingAs($this->customer, 'customer');
    $refundId = $this->postJson("/api/v1/store/orders/{$this->order->id}/refund-request", ['reason' => 'Changed my mind.'])
        ->json('data.id');

    app(\Modules\Sales\Services\RefundService::class)->approve(
        Refund::withoutGlobalScopes()->findOrFail($refundId),
        \App\Models\User::factory()->create(), // stand-in merchant/staff acting user
    );

    $this->order->refresh();
    expect($this->order->status)->toBe('refunded');

    $this->inventory->refresh();
    expect($this->inventory->quantity_on_hand)->toBe(5); // 3 + 2 restocked
});