<?php

namespace Tests\Feature\Payouts;

use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Models\Payout;
use App\Models\User;
use App\Notifications\PayoutNotification;
use App\Services\Payment\StripeConnect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Stripe\Transfer;
use Tests\TestCase;

class CreatePayoutStripeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Stripe rail must report configured for the payout method to validate.
        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.payouts.default' => 'stripe',
            'services.payouts.rails' => ['stripe'],
        ]);
    }

    private function onboardedExpert(): Expert
    {
        return Expert::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
            'stripe_account_id' => 'acct_1',
            'stripe_payouts_enabled' => true,
        ]);
    }

    public function test_it_pays_an_onboarded_expert_via_a_synchronous_transfer(): void
    {
        $this->mock(StripeConnect::class, function ($mock) {
            $mock->shouldReceive('refreshStatus')->andReturnTrue();
            $mock->shouldReceive('transfer')->once()
                ->andReturn(Transfer::constructFrom(['id' => 'tr_123']));
        });

        $expert = $this->onboardedExpert();

        $this->actingAs($this->admin)
            ->post(route('admin.payouts.store'), [
                'expert_id' => $expert->id,
                'amount' => '40.00',
                'currency' => 'USD',
                'method' => 'stripe',
                'idempotency_key' => 'key-1',
            ])
            ->assertRedirect(route('admin.payouts'));

        $payout = Payout::sole();
        $this->assertSame(4000, $payout->amount);
        $this->assertSame('stripe', $payout->method->value);
        $this->assertSame('tr_123', $payout->reference);
        $this->assertSame(PayoutStatus::Paid, $payout->status); // synchronous → paid
        $this->assertNotNull($payout->paid_at);
        Notification::assertSentOnDemand(PayoutNotification::class);
    }

    public function test_it_blocks_payout_when_stripe_payouts_not_enabled(): void
    {
        $this->mock(StripeConnect::class, function ($mock) {
            $mock->shouldReceive('refreshStatus')->andReturnFalse();
            $mock->shouldReceive('transfer')->never();
        });

        $expert = Expert::create([
            'name' => 'Not Ready',
            'email' => 'nr@example.com',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
            'stripe_account_id' => 'acct_2',
            'stripe_payouts_enabled' => false,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.payouts.store'), [
                'expert_id' => $expert->id,
                'amount' => '40.00',
                'currency' => 'USD',
                'method' => 'stripe',
                'idempotency_key' => 'key-2',
            ])
            ->assertSessionHasErrors('expert_id');

        $this->assertSame(0, Payout::count());
    }

    public function test_a_failed_transfer_marks_the_payout_failed(): void
    {
        $this->mock(StripeConnect::class, function ($mock) {
            $mock->shouldReceive('refreshStatus')->andReturnTrue();
            $mock->shouldReceive('transfer')->andThrow(new \RuntimeException('Insufficient funds'));
        });

        $expert = $this->onboardedExpert();

        $this->actingAs($this->admin)
            ->post(route('admin.payouts.store'), [
                'expert_id' => $expert->id,
                'amount' => '40.00',
                'currency' => 'USD',
                'method' => 'stripe',
                'idempotency_key' => 'key-3',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(PayoutStatus::Failed, Payout::sole()->status);
    }

    public function test_it_is_idempotent_on_the_key(): void
    {
        $this->mock(StripeConnect::class, function ($mock) {
            $mock->shouldReceive('refreshStatus')->andReturnTrue();
            $mock->shouldReceive('transfer')->once()    // only the first request transfers
                ->andReturn(Transfer::constructFrom(['id' => 'tr_1']));
        });

        $expert = $this->onboardedExpert();
        $payload = [
            'expert_id' => $expert->id,
            'amount' => '40.00',
            'currency' => 'USD',
            'method' => 'stripe',
            'idempotency_key' => 'dupe',
        ];

        $this->actingAs($this->admin)->post(route('admin.payouts.store'), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.payouts.store'), $payload)->assertRedirect();

        $this->assertSame(1, Payout::where('idempotency_key', 'dupe')->count());
    }

    public function test_any_submitted_currency_is_ignored_and_usd_is_forced(): void
    {
        $this->mock(StripeConnect::class, function ($mock) {
            $mock->shouldReceive('refreshStatus')->andReturnTrue();
            $mock->shouldReceive('transfer')->once()
                ->andReturn(Transfer::constructFrom(['id' => 'tr_usd']));
        });

        $expert = $this->onboardedExpert();

        // The system currency is fixed; a bogus submitted currency is ignored.
        $this->actingAs($this->admin)
            ->post(route('admin.payouts.store'), [
                'expert_id' => $expert->id,
                'amount' => '40.00',
                'currency' => 'XYZ',
                'method' => 'stripe',
                'idempotency_key' => 'cur',
            ])
            ->assertRedirect(route('admin.payouts'));

        $this->assertSame('USD', Payout::sole()->currency);
    }
}
