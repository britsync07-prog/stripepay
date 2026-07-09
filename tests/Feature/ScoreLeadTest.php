<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScoreLeadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function lead(): ConsultationRequest
    {
        return ConsultationRequest::create([
            'name' => 'Jane Prospect',
            'email' => 'jane@acme.test',
            'company' => 'Acme Ltd',
            'country' => 'AE',
            'message' => 'We want to expand into the UAE market.',
            'amount' => 250000,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => 'quoted',
            'lead_source' => 'form',
        ]);
    }

    private function fakeScore(int $score, array $flags): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'score' => $score,
                                    'summary' => 'Strong, engaged lead near close.',
                                    'flags' => $flags,
                                ]),
                            ]],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_admin_scores_a_lead_and_it_persists(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeScore(85, ['High intent', 'Follow-up needed']);

        $lead = $this->lead();

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.score', $lead))
            ->assertOk()
            ->assertJsonPath('score', 85)
            ->assertJsonPath('band', 'hot')
            ->assertJsonPath('flags.0', 'High intent');

        $lead->refresh();
        $this->assertSame(85, $lead->lead_score);
        $this->assertSame(['High intent', 'Follow-up needed'], $lead->lead_score_flags);
        $this->assertNotNull($lead->lead_scored_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'Jane Prospect')
            && $request['generationConfig']['responseMimeType'] === 'application/json');
    }

    public function test_score_is_clamped_to_0_100(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeScore(150, []);

        $lead = $this->lead();

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.score', $lead))
            ->assertOk()
            ->assertJsonPath('score', 100);
    }

    public function test_it_404s_for_a_non_lead_consultation(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $plan = ConsultationRequest::create([
            'name' => 'Buyer',
            'email' => 'buyer@acme.test',
            'amount' => 250000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.score', $plan))
            ->assertNotFound();
    }

    public function test_it_returns_503_when_gemini_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.score', $this->lead()))
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_score(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.leads.score', $this->lead()))
            ->assertForbidden();
    }
}
