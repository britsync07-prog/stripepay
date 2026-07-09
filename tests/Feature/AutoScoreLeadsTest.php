<?php

namespace Tests\Feature;

use App\Actions\Admin\ScoreLead;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AutoScoreLeadsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function lead(array $attributes = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'name' => 'Jane',
            'email' => 'jane@acme.test',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => 'new',
            'lead_source' => 'form',
        ], $attributes));
    }

    private function fakeScore(int $score): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'score' => $score,
                        'summary' => 'ok',
                        'flags' => [],
                    ])]]]],
                ],
            ]),
        ]);
    }

    public function test_storing_a_manual_lead_queues_a_score_job(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Queue::fake();

        $this->actingAs($this->admin())->post(route('admin.leads.store'), [
            'name' => 'New Prospect',
            'email' => 'prospect@acme.test',
        ]);

        Queue::assertPushed(JobDecorator::class, fn ($job) => $job->decorates(ScoreLead::class));
    }

    public function test_inbound_custom_plan_request_queues_a_score_job(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Role::findOrCreate('admin'); // the CustomPlanRequested listener notifies admins
        Notification::fake();
        Queue::fake();

        $this->post(route('custom-plan.store'), [
            'name' => 'Prospect',
            'email' => 'prospect@acme.test',
            'message' => 'We need help expanding into a new market.',
        ]);

        Queue::assertPushed(JobDecorator::class, fn ($job) => $job->decorates(ScoreLead::class));
    }

    public function test_no_score_job_is_queued_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);
        Queue::fake();

        $this->actingAs($this->admin())->post(route('admin.leads.store'), [
            'name' => 'New Prospect',
            'email' => 'prospect@acme.test',
        ]);

        // The prospect email still queues; only the AI scoring job must not.
        Queue::assertNotPushed(JobDecorator::class, fn ($job) => $job->decorates(ScoreLead::class));
    }

    public function test_score_all_queues_only_unscored_open_leads(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->lead(['email' => 'a@x.test']);                                   // unscored, open  → queued
        $this->lead(['email' => 'b@x.test', 'lead_score' => 60]);               // already scored  → skipped
        $this->lead(['email' => 'c@x.test', 'lead_stage' => 'won']);            // terminal        → skipped
        $this->lead(['email' => 'd@x.test', 'lead_stage' => 'lost']);           // terminal        → skipped

        Queue::fake();

        $this->actingAs($this->admin())->post(route('admin.leads.score-all'));

        Queue::assertPushed(JobDecorator::class, 1);
    }

    public function test_the_job_scores_and_persists(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeScore(72);

        $lead = $this->lead(['name' => 'Jane Prospect']);
        (new ScoreLead)->asJob($lead);

        $lead->refresh();
        $this->assertSame(72, $lead->lead_score);
        $this->assertNotNull($lead->lead_scored_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'Jane Prospect')
            && $request['generationConfig']['responseMimeType'] === 'application/json');
    }

    public function test_the_job_re_queues_itself_on_a_rate_limit(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'quota exceeded']],
                429,
            ),
        ]);
        Queue::fake();

        (new ScoreLead)->asJob($this->lead(), attempt: 1);

        // The 429 should trigger a re-dispatch (attempt 2), not a hard failure.
        Queue::assertPushed(JobDecorator::class, fn ($job) => $job->decorates(ScoreLead::class));
    }

    public function test_the_job_gives_up_after_the_final_attempt(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => []], 429),
        ]);
        Queue::fake();

        (new ScoreLead)->asJob($this->lead(), attempt: 4);

        // Final attempt logs and stops — no further re-dispatch.
        Queue::assertNothingPushed();
    }

    public function test_the_job_skips_a_non_lead(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();

        $notALead = ConsultationRequest::create([
            'name' => 'Buyer',
            'email' => 'buyer@x.test',
            'amount' => 500000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);
        (new ScoreLead)->asJob($notALead);

        $this->assertNull($notALead->fresh()->lead_score);
        Http::assertNothingSent();
    }

    public function test_score_all_flashes_an_error_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);
        Queue::fake();

        $this->lead();

        $this->actingAs($this->admin())
            ->post(route('admin.leads.score-all'))
            ->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_the_job_skips_a_terminal_lead(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();

        $lead = $this->lead(['lead_stage' => 'won']);
        (new ScoreLead)->asJob($lead);

        $this->assertNull($lead->fresh()->lead_score);
        Http::assertNothingSent();
    }
}
