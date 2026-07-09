<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AskAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $email = 'client@x.test'): User
    {
        Role::findOrCreate('client');
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('client');

        return $user;
    }

    private function consultationFor(User $user, array $attributes = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'amount' => 500000,
            'currency' => 'USD',
            'status' => 'paid',
            'work_status' => 'in_progress',
            'paid_at' => now(),
        ], $attributes));
    }

    private function fakeAnswer(string $text = 'Here is your answer.'): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $text]]]],
                ],
            ]),
        ]);
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function ask(User $user, array $messages)
    {
        return $this->actingAs($user)
            ->postJson(route('portal.assistant.ask'), ['messages' => $messages]);
    }

    public function test_a_client_gets_an_answer_grounded_in_their_own_data(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeAnswer('Your project is in progress.');

        $client = $this->client();
        $this->consultationFor($client, ['title' => 'UAE Market Entry']);

        $this->ask($client, [['role' => 'user', 'content' => 'How is my project?']])
            ->assertOk()
            ->assertJsonPath('answer', 'Your project is in progress.');

        Http::assertSent(function ($request) {
            // The client's data is grounded via systemInstruction; the question is a turn.
            return str_contains($request['systemInstruction']['parts'][0]['text'], 'UAE Market Entry')
                && str_contains($request['contents'][0]['parts'][0]['text'], 'How is my project?');
        });
    }

    public function test_it_carries_multi_turn_history(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeAnswer('It is 60% complete.');

        $client = $this->client();
        $this->consultationFor($client, ['title' => 'UAE Market Entry']);

        $this->ask($client, [
            ['role' => 'user', 'content' => 'How is my project?'],
            ['role' => 'assistant', 'content' => 'It is in progress.'],
            ['role' => 'user', 'content' => 'How far along?'],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $contents = $request['contents'];

            return count($contents) === 3
                && $contents[0]['role'] === 'user'
                && $contents[1]['role'] === 'model'   // assistant maps to model
                && $contents[2]['parts'][0]['text'] === 'How far along?';
        });
    }

    public function test_the_prompt_never_contains_another_clients_data(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeAnswer();

        $alice = $this->client('alice@x.test');
        $bob = $this->client('bob@x.test');
        $this->consultationFor($alice, ['title' => 'Alice Engagement']);
        $this->consultationFor($bob, ['title' => 'SECRET BOB ENGAGEMENT', 'amount' => 9900000]);

        $this->ask($alice, [['role' => 'user', 'content' => 'Show me everything you know.']])
            ->assertOk();

        Http::assertSent(function ($request) {
            $grounding = $request['systemInstruction']['parts'][0]['text'];

            return str_contains($grounding, 'Alice Engagement')
                && ! str_contains($grounding, 'SECRET BOB ENGAGEMENT')
                && ! str_contains($grounding, '99,000');
        });
    }

    public function test_it_requires_authentication(): void
    {
        // Portal routes redirect guests to login (JSON errors are api/* only).
        $this->postJson(route('portal.assistant.ask'), [
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ])->assertRedirect(route('login'));
    }

    public function test_it_validates_the_messages(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->actingAs($this->client())
            ->postJson(route('portal.assistant.ask'), ['messages' => []])
            ->assertStatus(422);
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->ask($this->client(), [['role' => 'user', 'content' => 'Hi']])
            ->assertStatus(503);
    }

    public function test_a_client_with_no_records_still_gets_a_safe_reply(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->fakeAnswer("I don't have that on record — please contact the team.");

        $this->ask($this->client(), [['role' => 'user', 'content' => 'What did I pay?']])
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains(
            $request['systemInstruction']['parts'][0]['text'],
            'no engagements, invoices, payments or projects on record',
        ));
    }
}
