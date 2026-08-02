<?php

namespace Modules\CRM\Services;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\CRM\Events\CustomerRegistered;
use Modules\CRM\Models\Customer;
use Modules\Sales\Models\Cart;
use Modules\Sales\Services\CartService;

class CustomerAuthService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly CartService $carts,
    ) {}

    /**
     * If a passwordless "guest" customer already exists for this email
     * (created by a prior guest checkout — see Sales\CheckoutController),
     * registration claims that record instead of failing on a uniqueness
     * collision or creating a confusing duplicate.
     */
    public function register(array $data, ?Cart $guestCart = null): Customer
    {
        $existingGuest = Customer::query()
            ->where('email', $data['email'])
            ->whereNull('password')
            ->first();

        if ($existingGuest) {
            $existingGuest->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? $existingGuest->phone,
            ]);
            $customer = $existingGuest;
        } else {
            $customer = Customer::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
            ]);
        }

        CustomerRegistered::dispatch($this->tenant->store->id, $customer->id);

        $this->completeLogin($customer, $guestCart);

        return $customer;
    }

    /**
     * Returns null on invalid credentials — caller returns a generic 422
     * (no user enumeration, per §6.2) rather than distinguishing
     * "no such email" from "wrong password".
     */
    public function login(string $email, string $password, ?Cart $guestCart = null): ?Customer
    {
        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer || ! $customer->password || ! Hash::check($password, $customer->password)) {
            return null;
        }

        $this->completeLogin($customer, $guestCart);

        return $customer;
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    private function completeLogin(Customer $customer, ?Cart $guestCart): void
    {
        Auth::guard('customer')->login($customer, remember: true);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        // Merge whatever the customer had in their guest cart into their
        // (possibly pre-existing) customer cart. Best-effort — see
        // CartService::mergeIntoCustomer's docblock for the merge strategy.
        if ($guestCart && $guestCart->isGuestCart()) {
            $this->carts->mergeIntoCustomer($guestCart, $customer);
        }
    }
}
