<?php

namespace Modules\Sales\Services;

use App\Support\Money\Money;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\CRM\Models\Customer;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\Models\Payment;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Exceptions\CheckoutException;
use Modules\Sales\Exceptions\OutOfStockException;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\Order;
use Modules\Tenant\Models\Store;

class CheckoutService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly InventoryService $inventory,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @return array{order: Order, client_secret: string}
     *
     * @throws CheckoutException
     * @throws OutOfStockException
     */
    public function initiate(
        Cart $cart,
        Customer $customer,
        array $shippingAddress,
        array $billingAddress,
        ?string $idempotencyKey = null,
    ): array {
        // Checked BEFORE the empty-cart guard below: a retried request after
        // the first attempt already succeeded (and cleared the cart) must
        // still return the original order, not a false "empty cart" error.
        if ($idempotencyKey) {
            $existing = Order::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                $payment = $existing->payments()->latest()->first();

                if ($payment && $payment->client_secret) {
                    return ['order' => $existing, 'client_secret' => $payment->client_secret];
                }
                // Order exists but never got a client_secret (gateway call
                // failed last time and compensation didn't run cleanly, or
                // this is a genuine same-millisecond race) — fall through
                // and treat it as a fresh attempt rather than getting stuck.
            }
        }

        if ($cart->items->isEmpty()) {
            throw new CheckoutException('EMPTY_CART', 'Cannot checkout an empty cart.');
        }

        // --- Step 1: everything internal (reservation, order, line items,
        // a placeholder payment row) happens inside one DB transaction. If
        // ANY line is out of stock, the whole transaction rolls back and
        // every reservation made so far in this attempt rolls back with it —
        // no manual compensation needed for this part.
        [$order, $payment] = DB::transaction(function () use ($cart, $customer, $shippingAddress, $billingAddress, $idempotencyKey) {
            $storeId = $this->tenant->store->id;
            $subtotal = Money::zero($cart->currency);

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'subtotal_cents' => 0, // patched below once items are in
                'total_cents' => 0,
                'currency' => $cart->currency,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->variant()->lockForUpdate()->firstOrFail();

                // Authoritative price: the live variant price, NOT the cart's
                // (possibly stale) snapshot — see CartService doc comments.
                $unitPrice = Money::fromMinorUnits($variant->price_cents, $cart->currency);
                $lineTotal = $unitPrice->multiply($cartItem->quantity);

                if ($variant->track_inventory) {
                    $this->inventory->reserve($storeId, $variant->id, $cartItem->quantity);
                }

                $order->items()->create([
                    'variant_id' => $variant->id,
                    'product_title_snapshot' => $variant->product->title,
                    'sku_snapshot' => $variant->sku,
                    'options_snapshot' => $variant->options,
                    'quantity' => $cartItem->quantity,
                    'unit_price_cents' => $unitPrice->amountMinor(),
                    'total_cents' => $lineTotal->amountMinor(),
                ]);

                $subtotal = $subtotal->add($lineTotal);
            }

            // Tax/shipping intentionally zero — Shipping module (rates) and
            // tax calculation aren't built yet (Phase 6+). Order schema
            // already has the columns so wiring them in later doesn't
            // require a migration.
            $order->update([
                'subtotal_cents' => $subtotal->amountMinor(),
                'total_cents' => $subtotal->amountMinor(),
                'placed_at' => now(),
            ]);

            $payment = $order->payments()->create([
                'provider' => $this->gateway->provider(),
                'status' => 'pending',
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
            ]);

            return [$order, $payment];
        });

        // --- Step 2: the external call, deliberately OUTSIDE the DB
        // transaction above (never hold DB locks across a network call to a
        // third party). If this fails, we compensate manually below.
        try {
            $intent = $this->gateway->createIntent(
                Money::fromMinorUnits($order->total_cents, $order->currency),
                metadata: ['order_id' => $order->id, 'store_id' => $this->tenant->store->id],
            );
        } catch (\Throwable $e) {
            Log::error('Payment gateway createIntent failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            $this->compensateFailedInitiation($order);

            throw new CheckoutException('PAYMENT_GATEWAY_ERROR', 'Could not start payment. Please try again.');
        }

        $payment->update([
            'provider_reference' => $intent->providerReference,
            'client_secret' => $intent->clientSecret,
        ]);

        return ['order' => $order, 'client_secret' => $intent->clientSecret];
    }

    /**
     * Called from Listeners\ConfirmOrderPayment (synchronous — see that
     * class for why). Idempotent: a duplicate/retried Stripe webhook for an
     * already-succeeded payment is a safe no-op.
     */
    public function confirmPayment(string $providerReference): void
    {
        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'stripe')
            ->where('provider_reference', $providerReference)
            ->first();

        if (! $payment) {
            Log::warning('Stripe webhook: no matching payment found', ['reference' => $providerReference]);

            return;
        }

        if ($payment->status === 'succeeded') {
            return;
        }

        $store = Store::query()->findOrFail($payment->store_id);

        Tenancy::run($store, function () use ($payment, $store) {
            DB::transaction(function () use ($payment, $store) {
                $order = Order::query()->findOrFail($payment->order_id);

                $payment->update(['status' => 'succeeded']);
                $order->transitionTo('paid');

                foreach ($order->items as $item) {
                    if ($item->variant?->track_inventory) {
                        $this->inventory->commit($store->id, $item->variant_id, $item->quantity);
                    }
                }
            });

            OrderPlaced::dispatch($store->id, $payment->order_id);
        });
    }

    /**
     * Called from Listeners\ReleaseOrderReservation. Releases the
     * reservation and cancels the order — the customer needs to start a new
     * checkout to try again (we don't reuse a failed order/payment).
     */
    public function failPayment(string $providerReference, ?string $reason): void
    {
        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'stripe')
            ->where('provider_reference', $providerReference)
            ->first();

        if (! $payment || $payment->status === 'succeeded') {
            return;
        }

        $store = Store::query()->findOrFail($payment->store_id);

        Tenancy::run($store, function () use ($payment, $store, $reason) {
            DB::transaction(function () use ($payment, $store, $reason) {
                $order = Order::query()->findOrFail($payment->order_id);

                $payment->update(['status' => 'failed', 'failure_reason' => ['message' => $reason]]);
                $order->transitionTo('cancelled', note: $reason ?? 'Payment failed');

                foreach ($order->items as $item) {
                    if ($item->variant?->track_inventory) {
                        $this->inventory->release($store->id, $item->variant_id, $item->quantity);
                    }
                }
            });
        });
    }

    private function compensateFailedInitiation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $storeId = $order->store_id;

            foreach ($order->items as $item) {
                if ($item->variant?->track_inventory) {
                    $this->inventory->release($storeId, $item->variant_id, $item->quantity);
                }
            }

            $order->transitionTo('cancelled', note: 'Payment gateway error during checkout');
        });
    }

    private function generateOrderNumber(): string
    {
        // Short, readable, store-scoped. Collision probability is
        // negligible but the unique index + a couple of retries make it
        // provably safe rather than merely "probably fine".
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = now()->format('ymd').'-'.strtoupper(Str::random(5));

            if (! Order::query()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new CheckoutException('ORDER_NUMBER_GENERATION_FAILED', 'Could not generate a unique order number.');
    }
}
