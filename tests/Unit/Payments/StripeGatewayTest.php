<?php

namespace Tests\Unit\Payments;

use App\Models\ConsultationRequest;
use App\Payments\Gateways\StripeGateway;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    public function test_metadata(): void
    {
        $gateway = new StripeGateway;

        $this->assertSame('stripe', $gateway->key());
        $this->assertSame('Card', $gateway->label());
        $this->assertSame('embedded', $gateway->mode());
    }

    public function test_is_configured_requires_both_keys(): void
    {
        config(['services.stripe.key' => null, 'services.stripe.secret' => null]);
        $this->assertFalse((new StripeGateway)->isConfigured());

        config(['services.stripe.key' => 'pk', 'services.stripe.secret' => null]);
        $this->assertFalse((new StripeGateway)->isConfigured());

        config(['services.stripe.key' => 'pk', 'services.stripe.secret' => 'sk']);
        $this->assertTrue((new StripeGateway)->isConfigured());
    }

    public function test_public_config_exposes_only_the_publishable_key(): void
    {
        config(['services.stripe.key' => 'pk_live_123']);

        $config = (new StripeGateway)->publicConfig(new ConsultationRequest);

        $this->assertSame(['publishableKey' => 'pk_live_123'], $config);
    }
}
