<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlansCrudTest extends TestCase
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
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Strategy Call',
            'slug' => 'strategy-call',
            'description' => 'A focused advisory session.',
            'price' => '1500',
            'billing_interval' => 'per_engagement',
            'features' => ['60 minutes', 'Written summary'],
            'outcome' => 'A clear plan.',
            'cta_label' => 'Book now',
            'is_featured' => true,
            'is_active' => true,
        ], $overrides);
    }

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Existing',
            'slug' => 'existing-'.uniqid(),
            'price' => 5000,
            'currency' => 'USD',
            'billing_interval' => 'per_engagement',
            'is_featured' => false,
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_creates_a_plan_with_price_stored_as_minor_units(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), $this->payload())
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::firstWhere('slug', 'strategy-call');

        $this->assertNotNull($plan);
        $this->assertSame(150000, $plan->price);
        $this->assertSame('USD', $plan->currency);
        $this->assertSame(['60 minutes', 'Written summary'], $plan->features);
        $this->assertGreaterThan(0, $plan->sort_order);
    }

    public function test_slug_is_required_unique_and_alpha_dash(): void
    {
        $this->plan(['slug' => 'taken-slug']);

        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), $this->payload(['slug' => 'taken-slug']))
            ->assertSessionHasErrors(['slug']);

        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), $this->payload(['slug' => 'has spaces!']))
            ->assertSessionHasErrors(['slug']);
    }

    public function test_price_and_billing_interval_are_validated(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), $this->payload(['price' => 'free']))
            ->assertSessionHasErrors(['price']);

        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), $this->payload(['billing_interval' => 'weekly']))
            ->assertSessionHasErrors(['billing_interval']);
    }

    public function test_admin_updates_a_plan_and_may_keep_its_own_slug(): void
    {
        $plan = $this->plan(['slug' => 'keep-me', 'price' => 5000]);

        $this->actingAs($this->admin)
            ->patch(route('admin.plans.update', $plan), $this->payload([
                'slug' => 'keep-me',
                'name' => 'Renamed Plan',
                'price' => '2000',
            ]))
            ->assertRedirect(route('admin.plans'));

        $plan->refresh();
        $this->assertSame('Renamed Plan', $plan->name);
        $this->assertSame(200000, $plan->price);
    }

    public function test_admin_deletes_a_plan(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->admin)
            ->delete(route('admin.plans.destroy', $plan))
            ->assertRedirect(route('admin.plans'));

        $this->assertModelMissing($plan);
    }

    public function test_reorder_rewrites_sort_order_to_match_the_given_sequence(): void
    {
        $a = $this->plan(['sort_order' => 1]);
        $b = $this->plan(['sort_order' => 2]);
        $c = $this->plan(['sort_order' => 3]);

        $this->actingAs($this->admin)
            ->post(route('admin.plans.reorder'), ['ids' => [$c->id, $a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);
    }

    public function test_a_non_admin_cannot_manage_plans(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $plan = $this->plan();

        $this->actingAs($client)
            ->post(route('admin.plans.store'), $this->payload(['slug' => 'sneaky']))
            ->assertForbidden();

        $this->actingAs($client)
            ->delete(route('admin.plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseMissing('plans', ['slug' => 'sneaky']);
        $this->assertModelExists($plan);
    }
}
