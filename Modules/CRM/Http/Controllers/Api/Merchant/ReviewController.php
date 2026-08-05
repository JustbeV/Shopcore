<?php

namespace Modules\CRM\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Http\Resources\ReviewResource;
use Modules\CRM\Models\Review;
use Modules\CRM\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviews,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ReviewResource::collection($reviews);
    }

    public function approve(string $reviewId)
    {
        $review = Review::query()->findOrFail($reviewId);
        $this->authorize('moderate', $review);

        $this->reviews->approve($review);

        return new ReviewResource($review->fresh());
    }

    public function reject(string $reviewId)
    {
        $review = Review::query()->findOrFail($reviewId);
        $this->authorize('moderate', $review);

        $this->reviews->reject($review);

        return new ReviewResource($review->fresh());
    }
}