<?php

namespace Tests\Feature\Admin;

use App\Enums\ConsultationStatus;
use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\User;
use App\Notifications\ConsultationExpertAssignedNotification;
use App\Notifications\ConsultationStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultationWorkflowTest extends TestCase
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

    private function consultation(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'name' => 'Paid Client',
            'email' => 'client@example.com',
            'amount' => 50000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Paid,
            'work_status' => ConsultationWorkStatus::New,
        ], $overrides));
    }

    private function activeExpert(array $overrides = []): Expert
    {
        return Expert::create(array_merge([
            'name' => 'Dr. Expert',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
        ], $overrides));
    }

    // --- Status state machine ----------------------------------------------

    public function test_a_legal_transition_updates_status_notifies_and_logs(): void
    {
        Notification::fake();

        $c = $this->consultation();

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), [
                'work_status' => 'in_progress',
            ])
            ->assertRedirect();

        $this->assertSame(ConsultationWorkStatus::InProgress, $c->fresh()->work_status);
        $this->assertDatabaseHas('activity_log', ['description' => 'Status changed']);
        Notification::assertSentTo($this->admin, ConsultationStatusUpdatedNotification::class);
    }

    public function test_an_illegal_transition_is_rejected_and_status_unchanged(): void
    {
        Notification::fake();

        $c = $this->consultation(['work_status' => ConsultationWorkStatus::New]);

        // New may only move to InProgress — not straight to Resolved or Closed.
        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'resolved'])
            ->assertSessionHasErrors(['work_status']);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'closed'])
            ->assertSessionHasErrors(['work_status']);

        $this->assertSame(ConsultationWorkStatus::New, $c->fresh()->work_status);
        Notification::assertNothingSent();
        $this->assertDatabaseMissing('activity_log', ['description' => 'Status changed']);
    }

    public function test_resolving_stamps_resolved_at(): void
    {
        $c = $this->consultation(['work_status' => ConsultationWorkStatus::InProgress]);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'resolved']);

        $this->assertNotNull($c->fresh()->resolved_at);
    }

    public function test_closing_stamps_closed_at(): void
    {
        $c = $this->consultation(['work_status' => ConsultationWorkStatus::InProgress]);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'closed']);

        $this->assertNotNull($c->fresh()->closed_at);
    }

    public function test_reopening_clears_resolved_and_closed_timestamps(): void
    {
        $c = $this->consultation([
            'work_status' => ConsultationWorkStatus::Closed,
            'resolved_at' => now(),
            'closed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'in_progress']);

        $fresh = $c->fresh();
        $this->assertSame(ConsultationWorkStatus::InProgress, $fresh->work_status);
        $this->assertNull($fresh->resolved_at);
        $this->assertNull($fresh->closed_at);
    }

    public function test_transitioning_to_the_same_status_is_a_noop(): void
    {
        Notification::fake();

        $c = $this->consultation(['work_status' => ConsultationWorkStatus::InProgress]);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'in_progress'])
            ->assertRedirect();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('activity_log', ['description' => 'Status changed']);
    }

    public function test_an_unknown_status_value_is_rejected(): void
    {
        $c = $this->consultation();

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'archived'])
            ->assertSessionHasErrors(['work_status']);
    }

    public function test_a_non_admin_cannot_change_status(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $c = $this->consultation();

        $this->actingAs($client)
            ->patch(route('admin.consultations.status', $c), ['work_status' => 'in_progress'])
            ->assertForbidden();

        $this->assertSame(ConsultationWorkStatus::New, $c->fresh()->work_status);
    }

    // --- Notes / contact log ------------------------------------------------

    public function test_adding_a_note_is_logged(): void
    {
        $c = $this->consultation();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.notes', $c), [
                'channel' => 'note',
                'message' => 'Spoke with the client about scope.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_log', ['description' => 'Note added']);
    }

    public function test_a_note_requires_a_message(): void
    {
        $c = $this->consultation();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.notes', $c), ['channel' => 'note', 'message' => ''])
            ->assertSessionHasErrors(['message']);
    }

    public function test_a_call_keeps_its_duration_but_a_note_drops_it(): void
    {
        $c = $this->consultation();

        $this->actingAs($this->admin)->post(route('admin.consultations.notes', $c), [
            'channel' => 'phone',
            'message' => 'Discovery call.',
            'duration' => 30,
        ]);
        $this->assertDatabaseHas('activity_log', ['description' => 'Contact logged']);

        $this->actingAs($this->admin)->post(route('admin.consultations.notes', $c), [
            'channel' => 'note',
            'message' => 'Internal note.',
            'duration' => 30,
        ]);

        $note = Activity::where('description', 'Note added')->latest()->first();
        $this->assertNull($note->properties['duration']);
    }

    public function test_a_non_admin_cannot_add_a_note(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.consultations.notes', $this->consultation()), [
                'channel' => 'note',
                'message' => 'hi',
            ])
            ->assertForbidden();
    }

    // --- Expert assignment --------------------------------------------------

    public function test_assigning_an_active_expert_sets_the_link_notifies_and_logs(): void
    {
        Notification::fake();

        $c = $this->consultation();
        $expert = $this->activeExpert();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.assign', $c), ['expert_id' => $expert->id])
            ->assertRedirect();

        $fresh = $c->fresh();
        $this->assertSame($expert->id, $fresh->expert_id);
        $this->assertNotNull($fresh->assigned_at);
        $this->assertDatabaseHas('activity_log', ['description' => 'Expert assigned']);
        Notification::assertSentTo($this->admin, ConsultationExpertAssignedNotification::class);
    }

    public function test_unassigning_clears_the_expert_and_timestamp(): void
    {
        $expert = $this->activeExpert();
        $c = $this->consultation(['expert_id' => $expert->id, 'assigned_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.assign', $c), ['expert_id' => null]);

        $fresh = $c->fresh();
        $this->assertNull($fresh->expert_id);
        $this->assertNull($fresh->assigned_at);
    }

    public function test_an_inactive_or_unknown_expert_is_rejected(): void
    {
        $c = $this->consultation();
        $inactive = $this->activeExpert(['is_active' => false, 'name' => 'Retired']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.assign', $c), ['expert_id' => $inactive->id])
            ->assertSessionHasErrors(['expert_id']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.assign', $c), ['expert_id' => 999999])
            ->assertSessionHasErrors(['expert_id']);

        $this->assertNull($c->fresh()->expert_id);
    }

    public function test_a_non_admin_cannot_assign_an_expert(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.consultations.assign', $this->consultation()), [
                'expert_id' => $this->activeExpert()->id,
            ])
            ->assertForbidden();
    }
}
