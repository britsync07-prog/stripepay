<?php

namespace Tests\Feature;

use App\Services\AI\Ai;
use App\Services\AI\AiException;
use App\Services\AI\Gemini;
use App\Services\AI\Groq;
use App\Services\AI\OpenAi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    private function useGroq(): void
    {
        config(['services.ai.default' => 'groq', 'services.groq.api_key' => 'test-key']);
    }

    private function fakeChat(string $content = 'Hello there.'): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => $content]]],
            ]),
        ]);
    }

    public function test_the_default_provider_is_selected_from_config(): void
    {
        config(['services.ai.default' => 'groq']);
        $this->assertInstanceOf(Groq::class, Ai::provider());

        config(['services.ai.default' => 'openai']);
        $this->assertInstanceOf(OpenAi::class, Ai::provider());

        config(['services.ai.default' => 'gemini']);
        $this->assertInstanceOf(Gemini::class, Ai::provider());
    }

    public function test_configured_reflects_the_active_provider(): void
    {
        config(['services.ai.default' => 'groq', 'services.groq.api_key' => null]);
        $this->assertFalse(Ai::configured());

        config(['services.groq.api_key' => 'test-key']);
        $this->assertTrue(Ai::configured());
    }

    public function test_generate_sends_openai_chat_shape(): void
    {
        $this->useGroq();
        $this->fakeChat('Drafted.');

        $this->assertSame('Drafted.', Ai::generate('Write a note', 'You are helpful'));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'api.groq.com/openai/v1/chat/completions')
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['messages'][0] === ['role' => 'system', 'content' => 'You are helpful']
                && $body['messages'][1] === ['role' => 'user', 'content' => 'Write a note'];
        });
    }

    public function test_chat_maps_model_turns_to_assistant(): void
    {
        $this->useGroq();
        $this->fakeChat();

        Ai::chat([
            ['role' => 'user', 'text' => 'Hi'],
            ['role' => 'model', 'text' => 'Hello'],
            ['role' => 'user', 'text' => 'More?'],
        ], 'Sys');

        Http::assertSent(function ($request) {
            $m = $request->data()['messages'];

            return $m[0]['role'] === 'system'
                && $m[1] === ['role' => 'user', 'content' => 'Hi']
                && $m[2] === ['role' => 'assistant', 'content' => 'Hello']
                && $m[3]['content'] === 'More?';
        });
    }

    public function test_generate_json_uses_json_object_mode(): void
    {
        $this->useGroq();
        $this->fakeChat(json_encode(['ok' => 'yes']));

        $result = Ai::generateJson('Give status', 'Sys', [
            'type' => 'OBJECT',
            'properties' => ['ok' => ['type' => 'STRING']],
        ]);

        $this->assertSame(['ok' => 'yes'], $result);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['response_format'] === ['type' => 'json_object']
                && str_contains($body['messages'][0]['content'], 'JSON object matching this schema');
        });
    }

    public function test_a_rate_limit_throws_aiexception_429(): void
    {
        $this->useGroq();
        Http::fake(['api.groq.com/*' => Http::response(['error' => []], 429)]);

        try {
            Ai::generate('Hi');
            $this->fail('Expected AiException');
        } catch (AiException $e) {
            $this->assertSame(429, $e->status);
        }
    }
}
