<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SummarizeNotesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_it_summarizes_raw_notes_with_action_items_and_follow_up(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'summary' => 'Client wants a proposal by next week.',
                    'action_items' => ['Send proposal', 'Schedule demo'],
                    'follow_up_in_days' => 7,
                ])]]]]],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.summarize-notes'), ['notes' => 'talked to client, they want a proposal, follow up next week, maybe a demo'])
            ->assertOk()
            ->assertJsonPath('summary', 'Client wants a proposal by next week.')
            ->assertJsonPath('action_items.0', 'Send proposal')
            ->assertJsonPath('follow_up_in_days', 7);

        Http::assertSent(fn ($request) => str_contains(
            $request['contents'][0]['parts'][0]['text'],
            'want a proposal',
        ));
    }

    public function test_zero_follow_up_becomes_null(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'summary' => 'Nothing pending.',
                    'action_items' => [],
                    'follow_up_in_days' => 0,
                ])]]]]],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.summarize-notes'), ['notes' => 'quick catch-up, all good'])
            ->assertOk()
            ->assertJsonPath('follow_up_in_days', null);
    }

    public function test_it_validates_notes_are_present(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.summarize-notes'), ['notes' => ''])
            ->assertStatus(422);
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.summarize-notes'), ['notes' => 'hi'])
            ->assertStatus(503);
    }

    public function test_non_admin_cannot_summarize(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->postJson(route('admin.summarize-notes'), ['notes' => 'hi'])
            ->assertForbidden();
    }
}
