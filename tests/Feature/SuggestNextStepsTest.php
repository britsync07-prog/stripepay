<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuggestNextStepsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function consultation(): ConsultationRequest
    {
        return ConsultationRequest::create([
            'name' => 'Jane Prospect',
            'email' => 'jane@acme.test',
            'country' => 'AE',
            'message' => 'We want to expand into the UAE market.',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => 'new',
            'lead_source' => 'form',
        ]);
    }

    private function fakeGemini(array $suggestion): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => json_encode($suggestion)]],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_admin_gets_structured_next_steps(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->fakeGemini([
            'stage' => 'New custom lead',
            'summary' => 'This lead has no price yet.',
            'steps' => [
                ['title' => 'Set the price', 'detail' => 'It is unpriced.', 'priority' => 'now'],
                ['title' => 'Send the payment link', 'detail' => 'After pricing.', 'priority' => 'soon'],
            ],
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.next-steps', $this->consultation()))
            ->assertOk()
            ->assertJsonPath('stage', 'New custom lead')
            ->assertJsonPath('steps.0.title', 'Set the price')
            ->assertJsonPath('steps.0.priority', 'now');

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
            && $request['generationConfig']['responseMimeType'] === 'application/json'
            && $request->hasHeader('x-goog-api-key', 'test-key'));
    }

    public function test_it_builds_state_for_a_paid_non_lead_consultation(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->fakeGemini([
            'stage' => 'In delivery',
            'summary' => 'Paid and assigned; focus on delivery.',
            'steps' => [
                ['title' => 'Open the project', 'detail' => 'Add milestones.', 'priority' => 'now'],
            ],
        ]);

        $paid = ConsultationRequest::create([
            'name' => 'Repeat Client',
            'email' => 'repeat@acme.test',
            'amount' => 500000,
            'currency' => 'USD',
            'status' => 'paid',
            'work_status' => 'in_progress',
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.next-steps', $paid))
            ->assertOk()
            ->assertJsonPath('stage', 'In delivery');

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Payment status: Paid (settled)')
                && str_contains($prompt, 'Not tracked as a CRM lead')
                && str_contains($prompt, 'Price: $5,000')
                && ! str_contains($prompt, 'NOT SET YET');
        });
    }

    public function test_it_surfaces_a_rate_limit_as_429(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'quota exceeded']],
                429,
            ),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.next-steps', $this->consultation()))
            ->assertStatus(429);
    }

    public function test_it_surfaces_malformed_json(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'not json at all']]]],
                ],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.next-steps', $this->consultation()))
            ->assertStatus(502);
    }

    public function test_it_returns_503_when_gemini_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.next-steps', $this->consultation()))
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_get_next_steps(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.consultations.next-steps', $this->consultation()))
            ->assertForbidden();
    }
}
