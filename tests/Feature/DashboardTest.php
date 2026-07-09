<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admins_can_visit_the_admin_dashboard()
    {
        // /dashboard is the admin dashboard; it requires the `admin` role.
        Role::findOrCreate('admin');
        Role::findOrCreate('client');

        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_non_admins_are_redirected_to_the_client_portal()
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('client');

        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }
}
