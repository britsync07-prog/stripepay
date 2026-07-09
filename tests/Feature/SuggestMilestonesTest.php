<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuggestMilestonesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function projectWithBrief(): Project
    {
        $consultation = ConsultationRequest::create([
            'name' => 'Client',
            'email' => 'c@x.test',
            'message' => 'We want to enter the UAE market and find distributors.',
            'amount' => 500000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        return Project::create([
            'consultation_request_id' => $consultation->id,
            'title' => 'UAE Market Entry',
            'status' => 'active',
            'completion_percentage' => 0,
        ]);
    }

    public function test_it_creates_ai_drafted_milestones_from_the_brief(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'milestones' => [
                        ['title' => 'Market analysis', 'description' => 'Study the market', 'due_in_days' => 7],
                        ['title' => 'Distributor shortlist', 'description' => 'Identify partners', 'due_in_days' => 21],
                    ],
                ])]]]]],
            ]),
        ]);

        $project = $this->projectWithBrief();

        $this->actingAs($this->admin())
            ->post(route('admin.projects.milestones.suggest', $project))
            ->assertRedirect();

        $milestones = $project->milestones()->orderBy('sort_order')->get();
        $this->assertCount(2, $milestones);
        $this->assertSame('Market analysis', $milestones[0]->title);
        $this->assertSame(now()->addDays(7)->toDateString(), $milestones[0]->due_date->toDateString());

        Http::assertSent(fn ($request) => str_contains(
            $request['contents'][0]['parts'][0]['text'],
            'enter the UAE market',
        ));
    }

    public function test_it_bounds_an_oversized_ai_plan(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'milestones' => array_map(fn (int $i) => [
                        'title' => str_repeat('Very long milestone title ', 20).$i,
                        'due_in_days' => 99999,
                    ], range(1, 15)),
                ])]]]]],
            ]),
        ]);

        $project = $this->projectWithBrief();

        $this->actingAs($this->admin())
            ->post(route('admin.projects.milestones.suggest', $project))
            ->assertRedirect();

        $milestones = $project->milestones()->get();
        $this->assertCount(10, $milestones);
        $this->assertLessThanOrEqual(255, mb_strlen($milestones[0]->title));
        $this->assertSame(now()->addDays(730)->toDateString(), $milestones[0]->due_date->toDateString());
    }

    public function test_a_provider_failure_flashes_an_error_and_creates_nothing(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $project = $this->projectWithBrief();

        $this->actingAs($this->admin())
            ->post(route('admin.projects.milestones.suggest', $project))
            ->assertRedirect();

        $this->assertSame(0, $project->milestones()->count());
    }

    public function test_it_flashes_and_creates_nothing_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $project = $this->projectWithBrief();

        $this->actingAs($this->admin())
            ->post(route('admin.projects.milestones.suggest', $project))
            ->assertRedirect();

        $this->assertSame(0, $project->milestones()->count());
    }

    public function test_non_admin_cannot_draft_milestones(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.projects.milestones.suggest', $this->projectWithBrief()))
            ->assertForbidden();
    }
}
