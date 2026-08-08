<?php

namespace Modules\Sales\Services;

use App\Models\User;
use App\Support\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Customer;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Sales\Events\OrderRefunded;
use Modules\Sales\Events\RefundRejected;
use Modules\Sales\Events\RefundRequested;
use Modules\Sales\Exceptions\CheckoutException;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;

class RefundService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * Full-refund only in this pass — the order.status enum has no
     * "partially_refunded" state (see §7.4's ERD), so partial refunds would
     * need a schema change to represent correctly rather than a hack on top
     * of this. Flagged for you rather than silently only refunding part of
     * the amount.
     */
    public function request(Order $order, Customer $customer, string $reason): Refund
    {
        if (Refund::query()->where('order_id', $order->id)->where('status', 'requested')->exists()) {
            throw new CheckoutException('REFUND_ALREADY_REQUESTED', 'A refund request is already pending for this order.');
        }

        $payment = $order->payments()->where('status', 'succeeded')->latest()->first();

        if (! $payment) {
            throw new CheckoutException('NO_SUCCEEDED_PAYMENT', 'This order has no successful payment to refund.');
        }

        $refund = Refund::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'requested_by_customer_id' => $customer->id,
            'status' => 'requested',
            'amount_cents' => $order->total_cents,
            'reason' => $reason,
        ]);

        RefundRequested::dispatch($refund->store_id, $refund->id);

        return $refund;
    }

    /**
     * @throws \Throwable if the gateway call fails — refund stays
     *                     'requested' (no partial state) so it's safe to retry.
     */
    public function approve(Refund $refund, User $approver): void
    {
        $payment = $refund->payment;
        $order = $refund->order;

        // Gateway call outside the DB transaction, same rationale as
        // CheckoutService::initiate — never hold locks across a network call.
        $result = $this->gateway->refund($payment, Money::fromMinorUnits($refund->amount_cents, $order->currency));

        DB::transaction(function () use ($refund, $payment, $order, $approver, $result) {
            $refund->update(['status' => 'processed', 'processed_at' => now()]);
            $payment->update(['status' => 'refunded']);
            $order->transitionTo('refunded', changedBy: $approver->id, note: "Refund {$result->providerReference}");

            foreach ($order->items as $item) {
                if ($item->variant?->track_inventory) {
                    $this->inventory->restock($order->store_id, $item->variant_id, $item->quantity);
                }
            }
        });

        OrderRefunded::dispatch($refund->store_id, $order->id, $refund->id);
    }

    public function reject(Refund $refund, User $rejector, ?string $note = null): void
    {
        $refund->update(['status' => 'rejected', 'decision_note' => $note]);

        RefundRejected::dispatch($refund->store_id, $refund->id);
    }
}