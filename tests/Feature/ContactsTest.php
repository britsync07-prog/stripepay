<?php

namespace Tests\Feature;

use App\Actions\Clients\ProvisionClientFromConsultation;
use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Contact;
use App\Models\Project;
use App\Models\User;
use App\Notifications\LeadCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The CRM contact behind leads/consultations: a reusable person record (no login)
 * that admins search + reuse, and that graduates into a client (User) on payment.
 */
class ContactsTest extends TestCase
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

    // --- Contact::resolve ---------------------------------------------------

    public function test_resolve_creates_a_contact_then_reuses_and_enriches_it_by_email(): void
    {
        $a = Contact::resolve(['email' => 'jo@x.com', 'name' => 'Jo', 'company' => 'Acme']);
        $b = Contact::resolve(['email' => 'jo@x.com', 'name' => 'Jo B', 'country' => 'US']);

        $this->assertTrue($a->is($b));
        $this->assertSame(1, Contact::count());

        $b->refresh();
        $this->assertSame('Jo B', $b->name);   // overwritten with the new value
        $this->assertSame('Acme', $b->company); // preserved
        $this->assertSame('US', $b->country);   // enriched
    }

    public function test_resolve_never_wipes_existing_fields_with_blanks(): void
    {
        $c = Contact::resolve(['email' => 'k@x.com', 'name' => 'K', 'company' => 'Co']);
        Contact::resolve(['email' => 'k@x.com', 'name' => '', 'company' => null]);

        $c->refresh();
        $this->assertSame('K', $c->name);
        $this->assertSame('Co', $c->company);
    }

    // --- Manual lead → contact + project + email ----------------------------

    public function test_adding_a_manual_lead_creates_a_contact_a_project_and_emails_the_prospect(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'name' => 'Pat Prospect',
            'email' => 'pat@x.com',
            'company' => 'Globex',
            'country' => 'AE',
        ])->assertRedirect();

        $lead = ConsultationRequest::firstOrFail();

        $this->assertNotNull($lead->contact_id);
        $this->assertSame('pat@x.com', $lead->contact->email);
        $this->assertSame('Globex', $lead->contact->company);
        $this->assertNull($lead->contact->user_id); // a lead is not a login account

        $project = Project::where('consultation_request_id', $lead->id)->firstOrFail();
        $this->assertSame(ProjectStatus::Planning, $project->status);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            LeadCreatedNotification::class,
            fn ($n, $ch, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'pat@x.com',
        );
    }

    public function test_adding_a_lead_for_a_known_email_reuses_the_same_contact(): void
    {
        $existing = Contact::factory()->create(['email' => 'dup@x.com', 'name' => 'Dup']);

        $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'name' => 'Dup Updated',
            'email' => 'dup@x.com',
        ])->assertRedirect();

        $this->assertSame(1, Contact::where('email', 'dup@x.com')->count());
        $this->assertTrue(ConsultationRequest::firstOrFail()->contact->is($existing));
    }

    public function test_a_custom_plan_request_also_links_a_contact(): void
    {
        $this->post(route('custom-plan.store'), [
            'name' => 'Web Lead',
            'email' => 'web@x.com',
            'company' => 'WebCo',
            'country' => 'US',
            'message' => 'Need help with expansion.',
        ])->assertRedirect('/');

        $lead = ConsultationRequest::firstOrFail();
        $this->assertNotNull($lead->contact_id);
        $this->assertSame('web@x.com', $lead->contact->email);
        $this->assertSame('WebCo', $lead->contact->company);
    }

    // --- Contact search -----------------------------------------------------

    public function test_contact_search_returns_matches_by_name_email_or_company(): void
    {
        Contact::factory()->create(['name' => 'Alice Anderson', 'email' => 'alice@x.com', 'company' => 'Wonder Inc']);
        Contact::factory()->create(['name' => 'Bob Brown', 'email' => 'bob@x.com', 'company' => null]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.contacts.search', ['q' => 'wonder']))
            ->assertOk()
            ->assertJsonCount(1, 'contacts')
            ->assertJsonPath('contacts.0.email', 'alice@x.com');
    }

    public function test_contact_search_needs_at_least_two_characters(): void
    {
        Contact::factory()->create(['name' => 'Al']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.contacts.search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'contacts');
    }

    public function test_contact_search_is_admin_only(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->getJson(route('admin.contacts.search', ['q' => 'alice']))
            ->assertForbidden();
    }

    // --- Payment graduates the contact into a client ------------------------

    public function test_provisioning_links_the_contact_to_the_new_client_on_payment(): void
    {
        Notification::fake();

        $contact = Contact::factory()->create(['email' => 'pay@x.com', 'name' => 'Payer', 'user_id' => null]);
        $lead = ConsultationRequest::create([
            'contact_id' => $contact->id,
            'name' => 'Payer',
            'email' => 'pay@x.com',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Paid,
            'paid_at' => now(),
        ]);

        $user = ProvisionClientFromConsultation::run($lead);

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('client'));
        $this->assertSame($user->id, $contact->fresh()->user_id);
    }

    public function test_a_guest_checkout_creates_an_enriched_contact_on_payment(): void
    {
        Notification::fake();

        // A plan/guest checkout has no contact yet but captured company/country.
        $req = ConsultationRequest::create([
            'name' => 'Guest Buyer',
            'email' => 'guest@x.com',
            'company' => 'GuestCo',
            'country' => 'FR',
            'phone' => '+331',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Paid,
            'paid_at' => now(),
        ]);
        $this->assertNull($req->contact_id);

        $user = ProvisionClientFromConsultation::run($req);

        $contact = $req->fresh()->contact;
        $this->assertNotNull($contact);
        $this->assertSame($user->id, $contact->user_id);
        $this->assertSame('GuestCo', $contact->company); // enriched, not just name/email
        $this->assertSame('FR', $contact->country);
    }

    public function test_paying_grants_the_client_role_to_an_existing_unroled_user(): void
    {
        Notification::fake();

        // A self-registered account that never carried the client role.
        $user = User::factory()->create(['email' => 'reg@x.com']);
        $this->assertFalse($user->hasRole('client'));

        $req = ConsultationRequest::create([
            'name' => 'Reg',
            'email' => 'reg@x.com',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Paid,
            'paid_at' => now(),
        ]);

        $result = ProvisionClientFromConsultation::run($req);

        $this->assertTrue($result->is($user));
        $this->assertTrue($user->fresh()->hasRole('client'));
    }

    // --- Quick-create a lead from an existing client ------------------------

    public function test_starting_a_lead_from_a_client_prefills_links_and_makes_a_project(): void
    {
        $contact = Contact::factory()->create([
            'email' => 'ret@x.com', 'name' => 'Returner', 'company' => 'RetCo', 'country' => 'AE',
        ]);
        $client = User::factory()->create(['name' => 'Returner', 'email' => 'ret@x.com']);
        $client->assignRole('client');
        $contact->update(['user_id' => $client->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.clients.lead', $client))
            ->assertRedirect();

        $lead = ConsultationRequest::where('user_id', $client->id)->firstOrFail();

        $this->assertSame(LeadStage::New, $lead->lead_stage);
        $this->assertSame('manual', $lead->lead_source);
        $this->assertSame($contact->id, $lead->contact_id);
        $this->assertSame('Returner', $lead->name);      // prefilled from the client
        $this->assertSame('RetCo', $lead->company);      // enriched from the contact
        $this->assertSame('AE', $lead->country);

        $this->assertSame(
            ProjectStatus::Planning,
            Project::where('consultation_request_id', $lead->id)->firstOrFail()->status,
        );
    }

    public function test_starting_a_lead_404s_for_a_non_client_user(): void
    {
        $notClient = User::factory()->create(); // no client role

        $this->actingAs($this->admin)
            ->post(route('admin.clients.lead', $notClient))
            ->assertNotFound();
    }

    public function test_starting_a_lead_from_a_client_is_admin_only(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.clients.lead', $client))
            ->assertForbidden();
    }
}
