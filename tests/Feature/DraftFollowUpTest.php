<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DraftFollowUpTest extends TestCase
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
            'name' => 'Nadia',
            'email' => 'nadia@x.test',
            'message' => 'Interested in your global mobility plan.',
            'amount' => 250000,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => 'quoted',
            'lead_source' => 'form',
            'next_follow_up_at' => now()->subDays(2),
        ]);
    }

    public function test_it_drafts_a_chaser_grounded_in_the_lead_history(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $lead = $this->lead();

        activity('consultation')
            ->performedOn($lead)
            ->withProperties(['type' => 'phone', 'message' => 'Discussed visa timelines on a call'])
            ->log('Contact logged');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'subject' => 'Following up on your global mobility plan',
                    'body' => 'Hi Nadia, following up on our call about visa timelines…',
                ])]]]]],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.follow-up-draft', $lead))
            ->assertOk()
            ->assertJsonPath('subject', 'Following up on your global mobility plan')
            ->assertJsonPath('body', 'Hi Nadia, following up on our call about visa timelines…');

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Nadia')
                && str_contains($prompt, 'Quoted')
                && str_contains($prompt, 'now overdue')
                && str_contains($prompt, 'Discussed visa timelines on a call');
        });
    }

    public function test_it_is_only_available_for_leads(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();

        $notALead = ConsultationRequest::create([
            'name' => 'Buyer',
            'email' => 'b@x.test',
            'amount' => 100000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.follow-up-draft', $notALead))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads.follow-up-draft', $this->lead()))
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_draft_follow_ups(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.leads.follow-up-draft', $this->lead()))
            ->assertForbidden();
    }
}
