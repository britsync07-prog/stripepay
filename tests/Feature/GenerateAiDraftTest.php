<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GenerateAiDraftTest extends TestCase
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
            'company' => 'Acme Ltd',
            'email' => 'jane@acme.test',
            'country' => 'AE',
            'message' => 'We want to expand into the UAE market.',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
    }

    public function test_admin_gets_an_ai_generated_draft(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Country::create(['code' => 'AE', 'name' => 'United Arab Emirates']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Dear Jane, here is our proposal...']]]],
                ],
            ]),
        ]);

        $admin = $this->admin();
        $lead = $this->lead();

        $this->actingAs($admin)
            ->postJson(route('admin.consultations.ai-draft', $lead), ['kind' => 'proposal'])
            ->assertOk()
            ->assertJson(['draft' => 'Dear Jane, here is our proposal...']);

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'Jane Prospect')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'United Arab Emirates')
            && $request->hasHeader('x-goog-api-key', 'test-key'));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'consultation',
            'subject_type' => $lead->getMorphClass(),
            'subject_id' => $lead->id,
            'causer_id' => $admin->id,
        ]);
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
            ->postJson(route('admin.consultations.ai-draft', $this->lead()), ['kind' => 'proposal'])
            ->assertStatus(429);
    }

    public function test_it_surfaces_a_gateway_error_as_502(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('boom', 500),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.ai-draft', $this->lead()), ['kind' => 'proposal'])
            ->assertStatus(502);
    }

    public function test_it_validates_the_kind(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.ai-draft', $this->lead()), ['kind' => 'bogus'])
            ->assertStatus(422);
    }

    public function test_it_returns_503_when_gemini_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.consultations.ai-draft', $this->lead()), ['kind' => 'email'])
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_draft(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.consultations.ai-draft', $this->lead()), ['kind' => 'email'])
            ->assertForbidden();
    }
}
