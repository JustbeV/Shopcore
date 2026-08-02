<?php

namespace Modules\CRM\Services;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\CRM\Models\Customer;

class CustomerPasswordResetService
{
    private const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    /**
     * Always returns a plaintext token to embed in the reset email/link.
     * Deliberately does NOT reveal whether the email exists — the caller
     * (PasswordResetController) returns the same generic response either
     * way, and this method is a no-op (returns null) for unknown emails so
     * there's nothing to leak.
     */
    public function requestReset(string $email): ?string
    {
        $customer = Customer::query()->where('email', $email)->whereNotNull('password')->first();

        if (! $customer) {
            return null;
        }

        $token = Str::random(64);

        DB::table('customer_password_reset_tokens')->updateOrInsert(
            ['store_id' => $this->tenant->store->id, 'email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        // TODO(Notifications module): send the actual email with a link
        // containing $token. Returned here (rather than only logged) so the
        // controller/tests can exercise the full reset flow before that
        // module exists.
        return $token;
    }

    /**
     * @return bool true if the password was reset
     */
    public function reset(string $email, string $token, string $newPassword): bool
    {
        $record = DB::table('customer_password_reset_tokens')
            ->where('store_id', $this->tenant->store->id)
            ->where('email', $email)
            ->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return false;
        }

        if (now()->diffInMinutes($record->created_at) > self::TOKEN_TTL_MINUTES) {
            return false;
        }

        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer) {
            return false;
        }

        $customer->update(['password' => Hash::make($newPassword)]);

        DB::table('customer_password_reset_tokens')
            ->where('store_id', $this->tenant->store->id)
            ->where('email', $email)
            ->delete();

        return true;
    }
}
