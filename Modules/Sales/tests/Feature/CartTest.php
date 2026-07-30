<?php

use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\ProductVariant;
use Modules\CRM\Models\Customer;
use Modules\Sales\Models\Cart;
use Modules\Sales\Services\CartService;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->variant = ProductVariant::factory()->create([
        'price_cents' => 2500,
        'track_inventory' => true,
    ]);
    InventoryItem::factory()->create([
        'variant_id' => $this->variant->id,
        'store_id' => $this->store->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);
});

it('creates a guest cart and returns a cart token on first add', function () {
    $response = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.total_quantity', 2)
        ->assertJsonPath('data.subtotal_cents', 5000);

    expect($response->headers->get('X-Cart-Token'))->not->toBeNull();
});

it('reuses the same guest cart when the cart token is replayed', function () {
    $first = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);
    $token = $first->headers->get('X-Cart-Token');

    $second = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $this->variant->id,
        'quantity' => 1,
    ], ['X-Cart-Token' => $token]);

    $second->assertJsonPath('data.total_quantity', 2);
    expect(Cart::query()->count())->toBe(1);
});

it('rejects adding more than available stock', function () {
    $response = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $this->variant->id,
        'quantity' => 999,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_STOCK')
        ->assertJsonPath('error.fields.available', 10);
});

it('removes a line when quantity is updated to zero', function () {
    $token = $this->postJson('/api/v1/store/cart/items', [
        'variant_id' => $this->variant->id,
        'quantity' => 1,
    ])->headers->get('X-Cart-Token');

    $cart = Cart::query()->where('session_token', $token)->firstOrFail();
    $item = $cart->items()->firstOrFail();

    $response = $this->patchJson("/api/v1/store/cart/items/{$item->id}", [
        'quantity' => 0,
    ], ['X-Cart-Token' => $token]);

    $response->assertJsonPath('data.total_quantity', 0);
    expect($cart->fresh()->items)->toBeEmpty();
});

it('merges a guest cart into the customer cart on login without duplicating stock beyond availability', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $guestCart = Cart::factory()->create(['store_id' => $this->store->id]);
    $guestCart->items()->create([
        'variant_id' => $this->variant->id,
        'quantity' => 3,
        'unit_price_cents' => 2500,
    ]);

    /** @var CartService $service */
    $service = app(CartService::class);
    $merged = $service->mergeIntoCustomer($guestCart, $customer);

    expect($merged->customer_id)->toBe($customer->id)
        ->and($merged->totalQuantity())->toBe(3)
        ->and(Cart::query()->find($guestCart->id))->toBeNull();
});
