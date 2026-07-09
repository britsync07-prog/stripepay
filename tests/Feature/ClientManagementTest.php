<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin-side client management: create, edit, export and bulk-import clients,
 * each keeping a linked CRM contact and (deliberately) leaving new accounts
 * unverified — portal access is still earned by payment / an explicit invite.
 */
class ClientManagementTest extends TestCase
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

    public function test_admin_creates_a_client_with_a_linked_contact_and_leaves_it_unverified(): void
    {
        $this->actingAs($this->admin)->post(route('admin.clients.store'), [
            'name' => 'New Client',
            'email' => 'nc@x.com',
            'company' => 'NCorp',
            'country' => 'AE',
            'phone' => '+97150',
        ])->assertRedirect();

        $user = User::where('email', 'nc@x.com')->firstOrFail();
        $this->assertTrue($user->hasRole('client'));
        $this->assertNull($user->email_verified_at); // no premature portal access

        $contact = Contact::where('email', 'nc@x.com')->firstOrFail();
        $this->assertSame($user->id, $contact->user_id);
        $this->assertSame('NCorp', $contact->company);
    }

    public function test_creating_a_client_with_an_existing_email_updates_not_duplicates(): void
    {
        $existing = User::factory()->create(['name' => 'Old', 'email' => 'dup@x.com']);
        $existing->assignRole('client');

        $this->actingAs($this->admin)->post(route('admin.clients.store'), [
            'name' => 'Updated Name',
            'email' => 'dup@x.com',
        ])->assertRedirect();

        $this->assertSame(1, User::where('email', 'dup@x.com')->count());
        $this->assertSame('Updated Name', $existing->fresh()->name);
    }

    public function test_creating_a_client_is_admin_only(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.clients.store'), ['name' => 'X', 'email' => 'x@x.com'])
            ->assertForbidden();
    }

    public function test_admin_edits_a_client_and_its_contact(): void
    {
        $client = User::factory()->create(['name' => 'Before', 'email' => 'e@x.com']);
        $client->assignRole('client');

        $this->actingAs($this->admin)->patch(route('admin.clients.update', $client), [
            'name' => 'After',
            'email' => 'e@x.com',
            'company' => 'EditCo',
            'country' => 'US',
        ])->assertRedirect();

        $this->assertSame('After', $client->fresh()->name);
        $this->assertSame('EditCo', Contact::where('email', 'e@x.com')->firstOrFail()->company);
    }

    public function test_export_streams_an_xlsx(): void
    {
        $c = User::factory()->create(['email' => 'exp@x.com']);
        $c->assignRole('client');

        $res = $this->actingAs($this->admin)->get(route('admin.clients.export'));

        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $res->headers->get('content-type'));
    }

    public function test_import_creates_clients_from_a_csv_and_skips_bad_rows(): void
    {
        $csv = "name,email,company,country\nAlice,alice@x.com,Acme,US\nBob,bob@x.com,,GB\n,bad-email,,\n";
        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $this->actingAs($this->admin)
            ->post(route('admin.clients.import'), ['file' => $file])
            ->assertRedirect();

        $alice = User::where('email', 'alice@x.com')->first();
        $this->assertNotNull($alice);
        $this->assertTrue($alice->hasRole('client'));
        $this->assertTrue(User::where('email', 'bob@x.com')->exists());
        $this->assertSame('Acme', Contact::where('email', 'alice@x.com')->firstOrFail()->company);

        // The invalid-email row is skipped, not imported.
        $this->assertFalse(Contact::where('email', 'bad-email')->exists());
    }

    public function test_import_is_admin_only(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $file = UploadedFile::fake()->createWithContent('clients.csv', "name,email\nA,a@x.com\n");

        $this->actingAs($client)
            ->post(route('admin.clients.import'), ['file' => $file])
            ->assertForbidden();
    }
}
