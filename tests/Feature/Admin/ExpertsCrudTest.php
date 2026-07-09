<?php

namespace Tests\Feature\Admin;

use App\Models\Expert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpertsCrudTest extends TestCase
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
            'name' => 'Dr. Advisor',
            'email' => 'advisor@example.com',
            'specialization' => 'Cross-border tax',
            'country' => 'AE',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
            'payout_notes' => null,
        ], $overrides);
    }

    private function expert(array $overrides = []): Expert
    {
        return Expert::create(array_merge([
            'name' => 'Existing Expert',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
        ], $overrides));
    }

    public function test_admin_creates_an_expert(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.experts.store'), $this->payload())
            ->assertRedirect(route('admin.experts'));

        $this->assertDatabaseHas('experts', [
            'name' => 'Dr. Advisor',
            'email' => 'advisor@example.com',
            'country' => 'AE',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
        ]);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.experts.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors(['name']);

        $this->assertSame(0, Expert::count());
    }

    public function test_email_must_be_unique_and_country_two_letters(): void
    {
        $this->expert(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.experts.store'), $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors(['email']);

        $this->actingAs($this->admin)
            ->post(route('admin.experts.store'), $this->payload([
                'email' => 'new@example.com',
                'country' => 'UAE',
            ]))
            ->assertSessionHasErrors(['country']);
    }

    public function test_payout_method_must_be_a_known_rail(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.experts.store'), $this->payload([
                'preferred_payout_method' => 'bitcoin',
            ]))
            ->assertSessionHasErrors(['preferred_payout_method']);
    }

    public function test_admin_updates_an_expert_and_may_keep_its_own_email(): void
    {
        $expert = $this->expert(['email' => 'keep@example.com', 'name' => 'Before']);

        $this->actingAs($this->admin)
            ->patch(route('admin.experts.update', $expert), $this->payload([
                'email' => 'keep@example.com',
                'name' => 'After',
                'is_active' => false,
            ]))
            ->assertRedirect();

        $expert->refresh();
        $this->assertSame('After', $expert->name);
        $this->assertFalse((bool) $expert->is_active);
    }

    public function test_a_non_admin_cannot_manage_experts(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.experts.store'), $this->payload(['email' => 'sneaky@example.com']))
            ->assertForbidden();

        $this->assertDatabaseMissing('experts', ['email' => 'sneaky@example.com']);
    }
}
