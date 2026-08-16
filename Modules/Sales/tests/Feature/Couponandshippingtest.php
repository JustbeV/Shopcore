<?php

use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\ProductVariant;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\Testing\FakeGateway;
use Modules\Sales\Models\Coupon;
use Modules\Shipping\Models\ShippingRate;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(PaymentGatewayInterface::class, FakeGateway::class);
    FakeGateway::reset();

    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);

    $this->variant = ProductVariant::factory()->create(['price_cents' => 10000, 'track_inventory' => false]);
    InventoryItem::factory()->create(['variant_id' => $this->variant->id, 'store_id' => $this->store->id]);

    $this->address = ['name' => 'Ada', 'line1' => '1 Way', 'city' => 'London', 'postal_code' => 'SW1A 1AA', 'country' => 'GB'];
});

function checkoutWithExtras(?string $couponCode = null, ?string $shippingRateId = null)
{
    $token = test()->postJson('/api/v1/store/cart/items', ['variant_id' => test()->variant->id, 'quantity' => 1])
        ->headers->get('X-Cart-Token');

    return test()->postJson('/api/v1/store/checkout', array_filter([
        'customer_email' => 'ada@example.com',
        'shipping_address' => test()->address,
        'coupon_code' => $couponCode,
        'shipping_rate_id' => $shippingRateId,
    ]), ['X-Cart-Token' => $token]);
}

it('applies a percentage coupon to the order total', function () {
    $coupon = Coupon::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);

    $response = checkoutWithExtras('SAVE10');

    $response->assertCreated()
        ->assertJsonPath('data.discount_cents', 1000) // 10% of 10000
        ->assertJsonPath('data.total_cents', 9000);

    expect($coupon->fresh()->times_used)->toBe(1);
});

it('rejects checkout with an unknown coupon code', function () {
    $response = checkoutWithExtras('DOES-NOT-EXIST');

    $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_COUPON');
});

it('rejects a coupon that has hit its usage limit', function () {
    Coupon::factory()->limitedTo(1)->create(['store_id' => $this->store->id, 'code' => 'ONEUSE', 'times_used' => 1]);

    $response = checkoutWithExtras('ONEUSE');

    $response->assertStatus(422)->assertJsonPath('error.code', 'COUPON_LIMIT_REACHED');
});

it('adds the selected shipping rate to the order total', function () {
    $rate = ShippingRate::factory()->create(['store_id' => $this->store->id, 'country_code' => 'GB', 'price_cents' => 750]);

    $response = checkoutWithExtras(null, $rate->id);

    $response->assertCreated()
        ->assertJsonPath('data.shipping_cents', 750)
        ->assertJsonPath('data.total_cents', 10750);
});

it('zeroes shipping when a free_shipping coupon is applied on top of a paid rate', function () {
    $rate = ShippingRate::factory()->create(['store_id' => $this->store->id, 'country_code' => 'GB', 'price_cents' => 750]);
    Coupon::factory()->freeShipping()->create(['store_id' => $this->store->id, 'code' => 'FREESHIP']);

    $response = checkoutWithExtras('FREESHIP', $rate->id);

    $response->assertCreated()
        ->assertJsonPath('data.shipping_cents', 0)
        ->assertJsonPath('data.total_cents', 10000); // subtotal only
});

it('lists shipping rates for a given country, including store-wide ALL rates', function () {
    ShippingRate::factory()->create(['store_id' => $this->store->id, 'country_code' => 'ALL', 'price_cents' => 500]);
    ShippingRate::factory()->create(['store_id' => $this->store->id, 'country_code' => 'GB', 'price_cents' => 750]);
    ShippingRate::factory()->create(['store_id' => $this->store->id, 'country_code' => 'US', 'price_cents' => 1200]);

    $response = $this->getJson('/api/v1/store/shipping-rates?country=GB');

    $response->assertOk()->assertJsonCount(2, 'data'); // ALL + GB, not US
});