<?php

namespace Tests\Unit\Payments;

use App\Payments\Gateways\PaypalGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\PaymentManager;
use Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    private function manager(): PaymentManager
    {
        return app(PaymentManager::class);
    }

    private function configureStripe(bool $on): void
    {
        config([
            'services.stripe.key' => $on ? 'pk_test' : null,
            'services.stripe.secret' => $on ? 'sk_test' : null,
        ]);
    }

    private function configurePaypal(bool $on): void
    {
        config([
            'services.paypal.client_id' => $on ? 'cid' : null,
            'services.paypal.client_secret' => $on ? 'secret' : null,
        ]);
    }

    public function test_it_resolves_each_gateway_by_key(): void
    {
        $this->assertInstanceOf(StripeGateway::class, $this->manager()->gateway('stripe'));
        $this->assertInstanceOf(PaypalGateway::class, $this->manager()->gateway('paypal'));
    }

    public function test_configured_returns_only_configured_gateways(): void
    {
        $this->configureStripe(true);
        $this->configurePaypal(true);
        $this->assertSame(['stripe', 'paypal'], array_keys($this->manager()->configured()));
    }

    public function test_configured_excludes_unconfigured_gateways(): void
    {
        $this->configureStripe(false);
        $this->configurePaypal(true);
        $this->assertSame(['paypal'], array_keys($this->manager()->configured()));
    }

    public function test_configured_is_empty_when_nothing_is_set_up(): void
    {
        $this->configureStripe(false);
        $this->configurePaypal(false);
        $this->assertSame([], array_keys($this->manager()->configured()));
    }
}
