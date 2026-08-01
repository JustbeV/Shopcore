<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('rejects a webhook with an invalid signature', function () {
    $response = $this->postJson('/api/v1/webhooks/stripe', ['type' => 'payment_intent.succeeded'], [
        'Stripe-Signature' => 'invalid',
    ]);

    $response->assertStatus(400);
});

it('returns 200 for an unhandled event type without dispatching anything', function () {
    // Signing a real payload requires the Stripe SDK's test helpers
    // (\Stripe\WebhookSignature::generateHeader) with a configured webhook
    // secret — wired up once STRIPE_WEBHOOK_SECRET is set in the test
    // environment. Left as a TODO rather than asserting against an
    // unsigned request, which would give a false sense of coverage.
})->todo();
