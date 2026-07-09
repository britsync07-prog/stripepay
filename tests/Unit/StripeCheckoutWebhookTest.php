<?php

namespace Tests\Unit;

use App\Services\Payment\StripeCheckout;
use Tests\TestCase;

/**
 * Directly exercises the real signature verifier (the feature tests mock it),
 * locking the fail-closed branches: no secret, no signature, or a forged one all
 * yield null so the webhook returns 400 and nothing is recorded.
 */
class StripeCheckoutWebhookTest extends TestCase
{
    private function checkout(): StripeCheckout
    {
        config(['services.stripe.secret' => 'sk_test']);

        return StripeCheckout::make();
    }

    public function test_it_fails_closed_when_no_webhook_secret_is_configured(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $this->assertNull($this->checkout()->verifyWebhook('{"id":"evt_1"}', 't=1,v1=abc'));
    }

    public function test_it_fails_closed_when_the_signature_header_is_empty(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->assertNull($this->checkout()->verifyWebhook('{"id":"evt_1"}', ''));
    }

    public function test_it_rejects_a_forged_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->assertNull($this->checkout()->verifyWebhook('{"id":"evt_1"}', 't=1,v1=forged'));
    }

    public function test_it_rejects_a_malformed_payload(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->assertNull($this->checkout()->verifyWebhook('not-json', 't=1,v1=forged'));
    }
}
