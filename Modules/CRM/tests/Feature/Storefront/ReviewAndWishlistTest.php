<?php

use Modules\Catalog\Models\Product;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Review;
use Modules\Tenant\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->active()->create(['domain' => 'test-store.platform.com']);
    $this->withServerVariables(['HTTP_HOST' => 'test-store.platform.com']);
    $this->product = Product::factory()->create(['title' => 'Test Widget']);
    $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
});

it('lets an authenticated customer submit a review, defaulting to pending', function () {
    $this->actingAs($this->customer, 'customer');

    $response = $this->postJson("/api/v1/store/products/{$this->product->id}/reviews", [
        'rating' => 5,
        'body' => 'Absolutely loved this product, exceeded expectations!',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'pending');
});

it('prevents a customer from reviewing the same product twice', function () {
    Review::factory()->create(['store_id' => $this->store->id, 'product_id' => $this->product->id, 'customer_id' => $this->customer->id]);
    $this->actingAs($this->customer, 'customer');

    $response = $this->postJson("/api/v1/store/products/{$this->product->id}/reviews", [
        'rating' => 4,
        'body' => 'Trying to review again, should be blocked.',
    ]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'REVIEW_ALREADY_EXISTS');
});

it('only shows approved reviews on the public listing', function () {
    Review::factory()->approved()->create(['store_id' => $this->store->id, 'product_id' => $this->product->id]);
    Review::factory()->create(['store_id' => $this->store->id, 'product_id' => $this->product->id]); // pending

    $response = $this->getJson("/api/v1/store/products/{$this->product->id}/reviews");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('lets a customer add and remove a product from their wishlist', function () {
    $this->actingAs($this->customer, 'customer');

    $this->postJson("/api/v1/store/wishlist/{$this->product->id}")->assertCreated();
    $this->getJson('/api/v1/store/wishlist')->assertOk()->assertJsonCount(1, 'data');

    $this->deleteJson("/api/v1/store/wishlist/{$this->product->id}")->assertOk();
    $this->getJson('/api/v1/store/wishlist')->assertOk()->assertJsonCount(0, 'data');
});