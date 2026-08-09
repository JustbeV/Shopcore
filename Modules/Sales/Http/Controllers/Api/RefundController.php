<?php

namespace Modules\Sales\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sales\Http\Requests\DecideRefundRequest;
use Modules\Sales\Http\Resources\RefundResource;
use Modules\Sales\Models\Refund;
use Modules\Sales\Services\RefundService;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', \Modules\Sales\Models\Order::class);

        $refunds = Refund::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return RefundResource::collection($refunds);
    }

    public function approve(DecideRefundRequest $request, string $refundId)
    {
        $refund = Refund::query()->findOrFail($refundId);
        $this->authorize('decide', $refund);

        $this->refunds->approve($refund, $request->user());

        return new RefundResource($refund->fresh());
    }

    public function reject(DecideRefundRequest $request, string $refundId)
    {
        $refund = Refund::query()->findOrFail($refundId);
        $this->authorize('decide', $refund);

        $this->refunds->reject($refund, $request->user(), $request->string('note'));

        return new RefundResource($refund->fresh());
    }
}