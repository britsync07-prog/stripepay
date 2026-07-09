<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AskConciergeTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): void
    {
        Plan::create([
            'name' => 'International Business Expansion',
            'slug' => 'international-business-expansion',
            'description' => 'Accelerated commercial growth abroad.',
            'price' => 500000,
            'currency' => 'USD',
            'billing_interval' => 'per_engagement',
            'features' => ['B2B introductions', 'Partner search'],
            'cta_label' => 'Get started',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function fakeAnswer(string $text = 'The Expansion plan fits market entry.'): void
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
    private function ask(array $messages)
    {
        // No authentication — this is a public endpoint.
        return $this->postJson(route('concierge.ask'), ['messages' => $messages]);
    }

    public function test_a_guest_gets_an_answer_grounded_in_the_active_plans(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->plan();
        $this->fakeAnswer();

        $this->ask([['role' => 'user', 'content' => 'Which plan for UAE market entry?']])
            ->assertOk()
            ->assertJsonPath('answer', 'The Expansion plan fits market entry.');

        Http::assertSent(function ($request) {
            return str_contains($request['systemInstruction']['parts'][0]['text'], 'International Business Expansion')
                && str_contains($request['contents'][0]['parts'][0]['text'], 'Which plan for UAE market entry?');
        });
    }

    public function test_it_carries_multi_turn_history(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->plan();
        $this->fakeAnswer('Yes, it includes partner search.');

        $this->ask([
            ['role' => 'user', 'content' => 'What plans do you offer?'],
            ['role' => 'assistant', 'content' => 'The International Business Expansion plan.'],
            ['role' => 'user', 'content' => 'Does it include partner introductions?'],
        ])->assertOk();

        Http::assertSent(fn ($request) => count($request['contents']) === 3
            && $request['contents'][1]['role'] === 'model'
            && $request['contents'][2]['parts'][0]['text'] === 'Does it include partner introductions?');
    }

    public function test_only_active_plans_are_sent_to_the_model(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->plan();
        Plan::create([
            'name' => 'RETIRED SECRET PLAN',
            'slug' => 'retired',
            'price' => 100,
            'currency' => 'USD',
            'billing_interval' => 'per_engagement',
            'features' => [],
            'cta_label' => 'x',
            'is_active' => false,
            'sort_order' => 2,
        ]);
        $this->fakeAnswer();

        $this->ask([['role' => 'user', 'content' => 'What do you offer?']])
            ->assertOk();

        Http::assertSent(fn ($request) => ! str_contains(
            $request['systemInstruction']['parts'][0]['text'],
            'RETIRED SECRET PLAN',
        ));
    }

    public function test_it_validates_the_messages(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $this->ask([])->assertStatus(422);
    }

    public function test_it_returns_503_when_ai_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->ask([['role' => 'user', 'content' => 'Hi']])->assertStatus(503);
    }
}
