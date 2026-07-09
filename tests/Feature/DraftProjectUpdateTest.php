<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DraftProjectUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function project(): Project
    {
        $consultation = ConsultationRequest::create([
            'name' => 'Omar',
            'email' => 'omar@x.test',
            'amount' => 800000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $project = Project::create([
            'consultation_request_id' => $consultation->id,
            'title' => 'Morocco Distribution Setup',
            'status' => 'active',
            'completion_percentage' => 50,
        ]);

        $project->milestones()->create([
            'title' => 'Regulatory review',
            'status' => 'completed',
            'sort_order' => 1,
            'completed_at' => now()->subDays(3),
        ]);
        $project->milestones()->create([
            'title' => 'Distributor shortlist',
            'status' => 'in_progress',
            'sort_order' => 2,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        return $project;
    }

    public function test_it_drafts_an_update_from_the_milestone_state(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hi Omar, the regulatory review is complete…']]]]],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.projects.update-draft', $this->project()))
            ->assertOk()
            ->assertJsonPath('draft', 'Hi Omar, the regulatory review is complete…');

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Omar')
                && str_contains($prompt, 'Morocco Distribution Setup')
                && str_contains($prompt, 'Regulatory review — Completed')
                && str_contains($prompt, 'Distributor shortlist — In progress')
                && str_contains($prompt, '50%');
        });
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.projects.update-draft', $this->project()))
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_draft_updates(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.projects.update-draft', $this->project()))
            ->assertForbidden();
    }
}
