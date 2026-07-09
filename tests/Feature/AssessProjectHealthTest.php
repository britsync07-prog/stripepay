<?php

namespace Tests\Feature;

use App\Actions\Admin\AssessProjectHealth;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessProjectHealthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function project(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'title' => 'UAE Market Entry',
            'status' => 'active',
            'completion_percentage' => 20,
        ], $attributes));
    }

    private function fakeVerdict(string $health, array $flags = []): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'health' => $health,
                        'summary' => 'Milestone overdue.',
                        'flags' => $flags,
                    ])]]]],
                ],
            ]),
        ]);
    }

    public function test_admin_assesses_a_project_and_it_persists(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeVerdict('at_risk', ['1 milestone overdue']);

        $project = $this->project();
        $project->milestones()->create([
            'title' => 'Market analysis',
            'status' => 'in_progress',
            'sort_order' => 1,
            'due_date' => now()->subDays(5),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.projects.health', $project))
            ->assertOk()
            ->assertJsonPath('health', 'at_risk')
            ->assertJsonPath('health_tone', 'warning');

        $project->refresh();
        $this->assertSame('at_risk', $project->health->value);
        $this->assertNotNull($project->health_checked_at);

        Http::assertSent(fn ($request) => str_contains($request['contents'][0]['parts'][0]['text'], 'Market analysis')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'OVERDUE')
            && $request['generationConfig']['responseMimeType'] === 'application/json');
    }

    public function test_an_unknown_health_value_falls_back_safely(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeVerdict('who_knows');

        $project = $this->project();

        $this->actingAs($this->admin())
            ->postJson(route('admin.projects.health', $project))
            ->assertOk()
            ->assertJsonPath('health', 'at_risk');
    }

    public function test_assess_all_queues_only_active_projects(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->project(['title' => 'Active A']);
        $this->project(['title' => 'Active B']);
        $this->project(['title' => 'Done', 'status' => 'completed']);

        Queue::fake();

        $this->actingAs($this->admin())->post(route('admin.projects.assess-all'));

        Queue::assertPushed(JobDecorator::class, 2);
    }

    public function test_the_job_re_queues_itself_on_a_rate_limit(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => []], 429),
        ]);
        Queue::fake();

        (new AssessProjectHealth)->asJob($this->project(), attempt: 1);

        Queue::assertPushed(JobDecorator::class, fn ($job) => $job->decorates(AssessProjectHealth::class));
    }

    public function test_the_verdict_is_not_exposed_to_the_client_portal(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->project([
            'user_id' => $client->id,
            'health' => 'stalled',
            'health_summary' => 'internal note',
        ]);

        $response = $this->actingAs($client)->get(route('portal.projects'));
        $project = $response->inertiaPage()['props']['projects'][0];

        $this->assertArrayNotHasKey('health', $project);
        $this->assertArrayNotHasKey('health_label', $project);
        $this->assertArrayNotHasKey('health_tone', $project);
        $this->assertArrayHasKey('uuid', $project);
    }

    public function test_the_job_skips_a_non_active_project(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();

        $project = $this->project(['status' => 'completed']);
        (new AssessProjectHealth)->asJob($project);

        $this->assertNull($project->fresh()->health);
        Http::assertNothingSent();
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.projects.health', $this->project()))
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_assess(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.projects.health', $this->project()))
            ->assertForbidden();
    }
}
