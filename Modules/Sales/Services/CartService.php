<?php

namespace Modules\Sales\Services;

use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\ProductVariant;
use Modules\CRM\Models\Customer;
use Modules\Sales\Exceptions\InsufficientStockException;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\CartItem;

class CartService
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    /**
     * Find or create the cart for an authenticated customer.
     * One active cart per (store, customer) — enforced by the DB unique index.
     */
    public function resolveForCustomer(Customer $customer): Cart
    {
        return Cart::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'currency' => $this->tenant->store->settings['default_currency'] ?? 'USD',
                'expires_at' => now()->addDays(30),
            ],
        )->load('items.variant');
    }

    /**
     * Find or create a guest cart by its client-held token.
     * If no token is given (first visit), a new cart + token is created and
     * the caller (CartController) is responsible for returning the token to
     * the client (via the X-Cart-Token response header) so it can be replayed
     * on subsequent requests.
     */
    public function resolveForGuest(?string $sessionToken): Cart
    {
        if ($sessionToken) {
            $cart = Cart::query()
                ->whereNull('customer_id')
                ->where('session_token', $sessionToken)
                ->first();

            if ($cart) {
                return $cart->load('items.variant');
            }
        }

        return Cart::query()->create([
            'session_token' => (string) Str::ulid(),
            'currency' => $this->tenant->store->settings['default_currency'] ?? 'USD',
            'expires_at' => now()->addDays(7),
        ])->load('items.variant');
    }

    /**
     * @throws InsufficientStockException
     */
    public function addItem(Cart $cart, string $variantId, int $quantity): CartItem
    {
        $variant = ProductVariant::query()->findOrFail($variantId);

        $existing = $cart->items()->where('variant_id', $variantId)->first();
        $desiredQuantity = $quantity + ($existing?->quantity ?? 0);

        $this->assertStockAvailable($variant, $desiredQuantity);

        return DB::transaction(function () use ($cart, $variant, $existing, $quantity, $desiredQuantity) {
            if ($existing) {
                $existing->update([
                    'quantity' => $desiredQuantity,
                    'unit_price_cents' => $variant->price_cents,
                ]);

                return $existing->fresh();
            }

            return $cart->items()->create([
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price_cents' => $variant->price_cents,
            ]);
        });
    }

    /**
     * @throws InsufficientStockException
     */
    public function updateQuantity(Cart $cart, string $cartItemId, int $quantity): CartItem
    {
        $item = $cart->items()->findOrFail($cartItemId);

        if ($quantity < 1) {
            $item->delete();

            return $item;
        }

        $this->assertStockAvailable($item->variant, $quantity);

        // Refresh the price snapshot to the current variant price whenever the
        // customer touches the line — keeps the cart view honest without
        // silently repricing lines the customer never revisited.
        $item->update([
            'quantity' => $quantity,
            'unit_price_cents' => $item->variant->price_cents,
        ]);

        return $item->fresh();
    }

    public function removeItem(Cart $cart, string $cartItemId): void
    {
        $cart->items()->where('id', $cartItemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Called from the login flow (AuthService::completeLogin for the
     * `customer` guard) immediately after a guest authenticates.
     *
     * Merge strategy: quantities of overlapping variants are summed (capped
     * by available stock); non-overlapping guest lines are moved over. The
     * guest cart row is then deleted. If the customer had no existing cart,
     * the guest cart is simply re-owned rather than copied.
     */
    public function mergeIntoCustomer(Cart $guestCart, Customer $customer): Cart
    {
        return DB::transaction(function () use ($guestCart, $customer) {
            $customerCart = Cart::query()->where('customer_id', $customer->id)->first();

            if (! $customerCart) {
                $guestCart->update([
                    'customer_id' => $customer->id,
                    'session_token' => null,
                ]);

                return $guestCart->fresh('items.variant');
            }

            foreach ($guestCart->items as $guestItem) {
                try {
                    $this->addItem($customerCart, $guestItem->variant_id, $guestItem->quantity);
                } catch (InsufficientStockException) {
                    // Best-effort merge: skip lines that no longer fit rather
                    // than failing the entire login flow over a cart conflict.
                    continue;
                }
            }

            $guestCart->delete();

            return $customerCart->fresh('items.variant');
        });
    }

    /**
     * @throws InsufficientStockException
     */
    private function assertStockAvailable(ProductVariant $variant, int $desiredQuantity): void
    {
        if (! $variant->track_inventory) {
            return;
        }

        $available = InventoryItem::query()
            ->where('variant_id', $variant->id)
            ->value('quantity_on_hand') ?? 0;

        $reserved = InventoryItem::query()
            ->where('variant_id', $variant->id)
            ->value('quantity_reserved') ?? 0;

        $sellable = $available - $reserved;

        if ($desiredQuantity > $sellable) {
            throw new InsufficientStockException($variant->id, $desiredQuantity, max($sellable, 0));
        }
    }
}
