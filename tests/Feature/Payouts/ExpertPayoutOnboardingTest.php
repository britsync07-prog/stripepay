<?php

namespace Tests\Feature\Payouts;

use App\Models\Expert;
use App\Models\User;
use App\Services\Payment\StripeConnect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpertPayoutOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.payouts.default' => 'stripe',
            'services.payouts.rails' => ['stripe'],
        ]);
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

    public function test_onboard_redirects_the_admin_to_stripe_hosted_onboarding(): void
    {
        $this->mock(StripeConnect::class, function ($m) {
            $m->shouldReceive('ensureAccount')->andReturn('acct_1');
            $m->shouldReceive('onboardingLink')->andReturn('https://connect.stripe.com/onboard/abc');
        });

        $expert = $this->expert();

        $this->actingAs($this->admin)
            ->post(route('admin.experts.payout.onboard', $expert))
            ->assertRedirect('https://connect.stripe.com/onboard/abc');
    }

    public function test_onboard_surfaces_a_failure_without_crashing(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('ensureAccount')
            ->andThrow(new \RuntimeException('Connect is not enabled on this account')));

        $expert = $this->expert();

        $this->actingAs($this->admin)
            ->post(route('admin.experts.payout.onboard', $expert))
            ->assertRedirect(); // bounced back, no 500

        $this->assertNull($expert->fresh()->stripe_account_id);
    }

    public function test_stripe_return_refreshes_status_and_returns_to_the_expert(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->once()->andReturnTrue());

        $expert = $this->expert(['stripe_account_id' => 'acct_1']);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.stripe.return', $expert))
            ->assertRedirect(route('admin.experts.edit', $expert));
    }

    public function test_stripe_refresh_redirects_to_a_fresh_onboarding_link(): void
    {
        $this->mock(StripeConnect::class, function ($m) {
            $m->shouldReceive('ensureAccount')->andReturn('acct_1');
            $m->shouldReceive('onboardingLink')->andReturn('https://connect.stripe.com/onboard/fresh');
        });

        $expert = $this->expert(['stripe_account_id' => 'acct_1']);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.stripe.refresh', $expert))
            ->assertRedirect('https://connect.stripe.com/onboard/fresh');
    }

    public function test_refresh_payout_status_action_rechecks_and_returns_to_the_expert(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->once()
            ->andReturnUsing(function (Expert $e) {
                $e->update(['stripe_payouts_enabled' => true]); // real method's side effect

                return true;
            }));

        $expert = $this->expert(['stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.payout.refresh', $expert))
            ->assertRedirect(route('admin.experts.edit', $expert));

        $this->assertTrue($expert->fresh()->stripe_payouts_enabled);
    }
}
