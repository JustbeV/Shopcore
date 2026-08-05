<?php

namespace Modules\CRM\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Models\Product;
use Modules\CRM\Http\Requests\SubmitReviewRequest;
use Modules\CRM\Http\Resources\ReviewResource;
use Modules\CRM\Models\Review;
use Modules\CRM\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviews,
    ) {}

    public function index(string $productId)
    {
        $reviews = Review::query()
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->latest()
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    public function store(SubmitReviewRequest $request, string $productId)
    {
        $product = Product::query()->findOrFail($productId);
        $customer = Auth::guard('customer')->user();

        $existing = Review::query()
            ->where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => ['code' => 'REVIEW_ALREADY_EXISTS', 'message' => 'You have already reviewed this product.'],
            ], 422);
        }

        $review = $this->reviews->submit($customer, $product, $request->integer('rating'), $request->string('body'));

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }
}