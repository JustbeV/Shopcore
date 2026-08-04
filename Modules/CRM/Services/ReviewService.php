<?php

namespace Modules\CRM\Services;

use Modules\Catalog\Models\Product;
use Modules\CRM\Events\ReviewSubmitted;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Review;
use Modules\Sales\Models\Order;

class ReviewService
{
    public function submit(Customer $customer, Product $product, int $rating, string $body): Review
    {
        $variantIds = $product->variants()->pluck('id');

        $verifiedOrderId = Order::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->whereHas('items', fn ($q) => $q->whereIn('variant_id', $variantIds))
            ->value('id');

        $review = Review::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $verifiedOrderId,
            'rating' => $rating,
            'body' => $body,
            'status' => 'pending',
        ]);

        ReviewSubmitted::dispatch($review->store_id, $review->id);

        return $review;
    }

    public function approve(Review $review): void
    {
        $review->update(['status' => 'approved']);
    }

    public function reject(Review $review): void
    {
        $review->update(['status' => 'rejected']);
    }
}