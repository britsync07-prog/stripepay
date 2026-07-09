<?php

namespace Tests\Feature;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Activity nudges a lead forward: a logged note → Contacted, an uploaded
 * attachment → Quoted. Forward-only — never regresses, never touches a non-lead
 * or a terminal (Won/Lost) stage.
 */
class LeadAutoAdvanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Role::findOrCreate('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function lead(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'name' => 'Lead',
            'email' => 'lead@x.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => LeadStage::New,
            'lead_source' => 'manual',
        ], $overrides));
    }

    private function note(ConsultationRequest $c): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.consultations.notes', $c), ['channel' => 'note', 'message' => 'Reached out.'])
            ->assertRedirect();
    }

    private function attach(ConsultationRequest $c): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.consultations.attachments.store', $c), [
                'files' => [UploadedFile::fake()->create('proposal.pdf', 100)],
            ])
            ->assertRedirect();
    }

    // --- Note → Contacted ---------------------------------------------------

    public function test_logging_a_note_advances_a_new_lead_to_contacted(): void
    {
        $lead = $this->lead();
        $this->note($lead);

        $this->assertSame(LeadStage::Contacted, $lead->fresh()->lead_stage);
    }

    public function test_a_note_never_regresses_a_further_stage(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Quoted]);
        $this->note($lead);

        $this->assertSame(LeadStage::Quoted, $lead->fresh()->lead_stage);
    }

    public function test_a_note_leaves_a_won_lead_untouched(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Won, 'status' => 'paid', 'paid_at' => now()]);
        $this->note($lead);

        $this->assertSame(LeadStage::Won, $lead->fresh()->lead_stage);
    }

    public function test_a_note_on_a_plan_consultation_is_not_a_lead_and_does_nothing(): void
    {
        $plan = Plan::create(['name' => 'P', 'slug' => 'p-'.uniqid(), 'price' => 5000, 'currency' => 'USD']);
        $consult = ConsultationRequest::create([
            'plan_id' => $plan->id,
            'name' => 'Buyer',
            'email' => 'b@x.com',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->note($consult);

        $this->assertNull($consult->fresh()->lead_stage);
    }

    public function test_uploading_an_attachment_no_longer_changes_the_stage(): void
    {
        // Quoting is tied to the offer (the price), not to arbitrary uploads.
        $lead = $this->lead(['lead_stage' => LeadStage::Contacted]);
        $this->attach($lead);

        $this->assertSame(LeadStage::Contacted, $lead->fresh()->lead_stage);
    }

    // --- Offer (price set) → Quoted -----------------------------------------

    public function test_setting_the_price_offer_advances_a_lead_to_quoted(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Contacted]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'title' => 'Advisory retainer',
                'amount' => '1500',
                'expires_in_days' => 14,
                'stay' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(LeadStage::Quoted, $lead->fresh()->lead_stage);
    }

    public function test_pricing_advances_a_new_lead_straight_to_quoted(): void
    {
        $lead = $this->lead(); // New

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'amount' => '1000',
                'expires_in_days' => 7,
                'stay' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(LeadStage::Quoted, $lead->fresh()->lead_stage);
    }
}
