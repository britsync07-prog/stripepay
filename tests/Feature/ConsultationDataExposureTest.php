<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultationDataExposureTest extends TestCase
{
    use RefreshDatabase;

    private const INTERNAL_FIELDS = ['is_lead', 'lead_stage', 'lead_stage_label', 'lead_stage_tone'];

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client');
        Role::findOrCreate('admin');
        $this->client = User::factory()->create(['email' => 'client@example.com']);
        $this->client->assignRole('client');
    }

    private function makeLead(): ConsultationRequest
    {
        Country::firstOrCreate(['code' => 'AE'], ['name' => 'United Arab Emirates']);

        return ConsultationRequest::create([
            'plan_id' => null,
            'user_id' => $this->client->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'country' => 'AE',
            'amount' => 250000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
            'lead_stage' => 'quoted',
            'lead_source' => 'form',
        ]);
    }

    public function test_admin_page_exposes_the_lead_stage(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.consultation', $this->makeLead()))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/consultation')
                ->where('consultation.is_lead', true)
                ->where('consultation.lead_stage', 'quoted')
                ->where('consultation.lead_stage_label', 'Quoted')
                ->where('consultation.lead_stage_tone', 'warning')
                ->where('consultation.country', 'United Arab Emirates')
                ->missing('workStatusLabel')
            );
    }

    public function test_portal_pages_never_expose_crm_pipeline_fields(): void
    {
        $lead = $this->makeLead();

        $response = $this->actingAs($this->client)->get(route('portal.consultation', $lead));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('portal/consultation')
            ->has('consultation.uuid')
        );

        $consultation = $response->inertiaPage()['props']['consultation'];
        $this->assertSame([], array_intersect(self::INTERNAL_FIELDS, array_keys($consultation)));

        $this->actingAs($this->client)
            ->get(route('portal.consultations'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/consultations')
                ->has('consultations.0.uuid')
                ->missing('consultations.0.is_lead')
                ->missing('consultations.0.lead_stage')
            );

        $this->actingAs($this->client)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/dashboard')
                ->has('recent.0.uuid')
                ->missing('recent.0.is_lead')
                ->missing('recent.0.lead_stage')
            );
    }
}
