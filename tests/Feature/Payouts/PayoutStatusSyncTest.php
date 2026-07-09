<?php

namespace Tests\Feature\Payouts;

use App\Models\Expert;
use App\Models\User;
use App\Services\Payment\StripeConnect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The RefreshesPayoutStatus concern re-checks a mid-onboarding expert's status
 * from Stripe on admin screen loads (experts list + payout form), so the UI is
 * correct without a webhook. Already-ready and not-set-up experts make no call.
 */
class PayoutStatusSyncTest extends TestCase
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

    public function test_experts_list_auto_syncs_a_pending_experts_status(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->once()
            ->andReturnUsing(function (Expert $e) {
                $e->update(['stripe_payouts_enabled' => true]);

                return true;
            }));

        $expert = $this->expert(['stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => false]);

        $this->actingAs($this->admin)->get(route('admin.experts'))->assertOk();

        $this->assertTrue($expert->fresh()->stripe_payouts_enabled); // cache corrected on load
    }

    public function test_experts_list_skips_an_already_enabled_expert(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->never());

        $this->expert(['stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => true]);

        $this->actingAs($this->admin)->get(route('admin.experts'))->assertOk();
    }

    public function test_experts_list_skips_an_expert_with_no_account(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->never());

        $this->expert(); // never onboarded

        $this->actingAs($this->admin)->get(route('admin.experts'))->assertOk();
    }

    public function test_a_stripe_outage_does_not_break_the_experts_list(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')
            ->andThrow(new \RuntimeException('Stripe is down')));

        $expert = $this->expert(['stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => false]);

        // guarded by rescue() — page still renders, status simply unchanged
        $this->actingAs($this->admin)->get(route('admin.experts'))->assertOk();
        $this->assertFalse($expert->fresh()->stripe_payouts_enabled);
    }

    public function test_payout_form_auto_syncs_pending_experts_too(): void
    {
        $this->mock(StripeConnect::class, fn ($m) => $m->shouldReceive('refreshStatus')->once()
            ->andReturnUsing(function (Expert $e) {
                $e->update(['stripe_payouts_enabled' => true]);

                return true;
            }));

        $expert = $this->expert(['stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => false]);

        $this->actingAs($this->admin)->get(route('admin.payouts.create'))->assertOk();

        $this->assertTrue($expert->fresh()->stripe_payouts_enabled);
    }
}
