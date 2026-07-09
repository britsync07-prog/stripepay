<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ConsultationRequest $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->consultation = ConsultationRequest::create([
            'amount' => 5000, 'currency' => 'USD', 'status' => 'paid',
        ]);
    }

    private function seedEntries(): void
    {
        activity('consultation')->performedOn($this->consultation)
            ->withProperties(['type' => 'status', 'message' => 'Status changed', 'from_label' => 'New', 'to_label' => 'In progress'])
            ->log('Status changed');

        activity('payment')->causedBy($this->admin)->performedOn($this->consultation)
            ->withProperties(['type' => 'payment'])
            ->log('Consultation payment completed');
    }

    public function test_admin_can_view_the_audit_trail(): void
    {
        $this->seedEntries();

        $this->actingAs($this->admin)
            ->get(route('admin.activity'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/activity')
                ->has('activities.data', 2)
                ->where('stats.total', 2)
                ->has('logNames', 2)
            );
    }

    public function test_it_can_filter_by_log_name(): void
    {
        $this->seedEntries();

        $this->actingAs($this->admin)
            ->get(route('admin.activity', ['filter' => ['log_name' => 'payment']]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.log_name', 'payment')
                ->where('activities.data.0.causer', $this->admin->name)
            );
    }

    public function test_it_resolves_the_subject_label_and_link(): void
    {
        $this->seedEntries();

        $this->actingAs($this->admin)
            ->get(route('admin.activity', ['filter' => ['log_name' => 'payment']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.data.0.subject', $this->consultation->reference)
                ->where('activities.data.0.subject_url', route('admin.consultation', $this->consultation, false))
            );
    }

    public function test_it_resolves_links_for_non_consultation_and_null_subjects(): void
    {
        $project = Project::create(['title' => 'P', 'status' => ProjectStatus::Active]);
        activity('project')->performedOn($project)->log('Project created');
        activity('system')->log('A system event with no subject');

        $this->actingAs($this->admin)
            ->get(route('admin.activity'))
            ->assertInertia(fn (Assert $page) => $page
                // latest-first: the subject-less system entry is row 0.
                ->where('activities.data.0.subject', null)
                ->where('activities.data.0.subject_url', null)
                ->where('activities.data.1.subject', $project->reference)
                ->where('activities.data.1.subject_url', route('admin.projects.show', $project, false))
            );
    }

    public function test_a_non_admin_cannot_view_the_audit_trail(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('admin.activity'))->assertForbidden();
    }
}
