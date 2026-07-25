<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\app\Models\StoreDomain;

/**
 * Checks for a `_shopcore-verify.{hostname}` TXT record matching the
 * domain's verification_token. Dispatched when a merchant adds a
 * custom domain, and re-dispatched (with backoff) if verification
 * hasn't succeeded yet — DNS propagation can take anywhere from
 * minutes to ~48 hours, so this is designed to be retried, not to
 * succeed on the first attempt.
 *
 * SSL provisioning (architecture §4.3: Cloudflare for SaaS / Let's
 * Encrypt) is intentionally NOT part of this job — that's a follow-up
 * once verification succeeds, and depends on which hosting provider
 * is chosen (open question §17 in the architecture doc).
 */
final class VerifyStoreDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600, 21600];

    public function __construct(
        private readonly string $storeDomainId,
    ) {}

    public function handle(): void
    {
        $domain = StoreDomain::query()->find($this->storeDomainId);

        if ($domain === null || $domain->isVerified()) {
            return;
        }

        $domain->update(['last_checked_at' => now()]);

        if ($this->hasMatchingTxtRecord($domain)) {
            $domain->update([
                'verification_status' => StoreDomain::STATUS_VERIFIED,
                'verified_at' => now(),
            ]);

            return;
        }

        if ($this->attempts() >= $this->tries) {
            $domain->update(['verification_status' => StoreDomain::STATUS_FAILED]);
        }
    }

    private function hasMatchingTxtRecord(StoreDomain $domain): bool
    {
        $records = @dns_get_record("_shopcore-verify.{$domain->hostname}", DNS_TXT) ?: [];

        foreach ($records as $record) {
            if (($record['txt'] ?? null) === $domain->verification_token) {
                return true;
            }
        }

        return false;
    }
}