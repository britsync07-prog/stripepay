<?php

namespace Tests\Feature\Payouts;

use App\Models\Expert;
use App\Payments\Contracts\PayoutRail;
use App\Payments\PayoutRailManager;
use App\Payments\PayoutResult;
use App\Services\Payment\StripeConnect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Transfer;
use Tests\TestCase;

class StripeRailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.payouts.default' => 'stripe',
            'services.payouts.rails' => ['stripe'],
        ]);
    }

    private function rail(): PayoutRail
    {
        return app(PayoutRailManager::class)->rail('stripe');
    }

    private function expert(array $attrs = []): Expert
    {
        return Expert::create(array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
        ], $attrs));
    }

    public function test_identity_and_configuration(): void
    {
        $this->assertSame('stripe', $this->rail()->key());
        $this->assertSame('Stripe', $this->rail()->label());
        $this->assertTrue($this->rail()->isConfigured());

        config(['services.stripe.secret' => '']);
        $this->assertFalse($this->rail()->isConfigured());
    }

    public function test_manager_resolves_and_lists_the_stripe_rail(): void
    {
        $manager = app(PayoutRailManager::class);

        $this->assertSame('stripe', $manager->getDefaultDriver());
        $this->assertInstanceOf(PayoutRail::class, $manager->rail('stripe'));
        $this->assertSame(['stripe'], array_keys($manager->configured()));
    }

    public function test_ensure_recipient_delegates_to_connect(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('ensureAccount')->once()->andReturn('acct_X'));

        $this->assertSame('acct_X', $this->rail()->ensureRecipient($this->expert()));
    }

    public function test_recipient_ready_is_false_without_an_account_and_never_calls_stripe(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->never());

        $this->assertFalse($this->rail()->recipientReady($this->expert())); // no stripe_account_id
    }

    public function test_recipient_ready_does_a_live_status_check_when_an_account_exists(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->once()->andReturnTrue());

        $this->assertTrue($this->rail()->recipientReady($this->expert(['stripe_account_id' => 'acct_1'])));
    }

    public function test_pay_returns_a_synchronous_paid_result(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('transfer')->once()
            ->andReturn(Transfer::constructFrom(['id' => 'tr_9'])));

        $result = $this->rail()->pay($this->expert(['stripe_account_id' => 'acct_1']), 500, 'USD', 'key', 'memo');

        $this->assertInstanceOf(PayoutResult::class, $result);
        $this->assertSame('tr_9', $result->reference);
        $this->assertSame('paid', $result->status);
    }

    public function test_payment_status_is_null_because_transfers_are_synchronous(): void
    {
        $this->assertNull($this->rail()->paymentStatus('tr_9'));
    }

    public function test_onboarding_url_ensures_an_account_then_builds_a_link(): void
    {
        $this->mock(StripeConnect::class, function ($m) {
            $m->shouldReceive('ensureAccount')->once()->andReturn('acct_1');
            $m->shouldReceive('onboardingLink')->once()->andReturn('https://connect.stripe.com/setup/x');
        });

        $this->assertSame(
            'https://connect.stripe.com/setup/x',
            $this->rail()->onboardingUrl($this->expert(['stripe_account_id' => 'acct_1'])),
        );
    }
}
