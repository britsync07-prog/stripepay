<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalOutstandingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client');
        $this->client = User::factory()->create(['email' => 'client@example.com']);
        $this->client->assignRole('client');
    }

    private function makeRequest(array $attributes): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'user_id' => $this->client->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
        ], $attributes));
    }

    public function test_unpriced_custom_lead_is_not_shown_as_outstanding(): void
    {
        $this->makeRequest(['title' => 'Custom plan request', 'amount' => 0]);

        $this->actingAs($this->client)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/dashboard')
                ->where('outstanding', [])
            );
    }

    public function test_priced_pending_request_is_shown_as_outstanding(): void
    {
        $req = $this->makeRequest([
            'title' => 'Custom Advisory',
            'amount' => 250000,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($this->client)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/dashboard')
                ->has('outstanding', 1)
                ->where('outstanding.0.uuid', $req->uuid)
                ->where('outstanding.0.title', 'Custom Advisory')
            );
    }

    public function test_expired_priced_request_is_not_shown_as_outstanding(): void
    {
        $this->makeRequest([
            'title' => 'Stale',
            'amount' => 250000,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($this->client)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/dashboard')
                ->where('outstanding', [])
            );
    }
}
