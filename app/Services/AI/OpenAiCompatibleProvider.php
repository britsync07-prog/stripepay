<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared driver for OpenAI-compatible Chat Completions APIs (OpenAI, Groq, and
 * most others). Concrete drivers only supply their config namespace + label.
 */
abstract class OpenAiCompatibleProvider implements AiProvider
{
    private const TEMPERATURE_TEXT = 0.7;

    private const TEMPERATURE_JSON = 0.3;

    private const RETRY_DELAY_MICROSECONDS = 400_000;

    private const REQUEST_TIMEOUT_SECONDS = 60;

    /** The config('services.*') key holding api_key / model / base_url. */
    abstract protected function configKey(): string;

    /** Human name used in error messages/logs. */
    abstract protected function name(): string;

    public function configured(): bool
    {
        return filled(config("services.{$this->configKey()}.api_key"));
    }

    public function generate(string $prompt, ?string $system = null): string
    {
        return $this->request($this->withSystem($system, [
            ['role' => 'user', 'content' => $prompt],
        ]), self::TEMPERATURE_TEXT);
    }

    /**
     * @param  array<int,array{role:string,text:string}>  $turns
     */
    public function chat(array $turns, ?string $system = null): string
    {
        $messages = array_map(fn (array $turn) => [
            'role' => $turn['role'] === 'model' ? 'assistant' : 'user',
            'content' => $turn['text'],
        ], $turns);

        return $this->request($this->withSystem($system, $messages), self::TEMPERATURE_TEXT);
    }

    /**
     * @param  array<string,mixed>  $schema
     * @return array<string,mixed>
     */
    public function generateJson(string $prompt, ?string $system, array $schema): array
    {
        // json_object mode needs the shape described in the prompt (and the word
        // "json" present), rather than Gemini's native responseSchema.
        $system = trim(($system ?? '')."\n\nRespond ONLY with a JSON object matching this schema:\n".json_encode($schema));

        $text = $this->request(
            $this->withSystem($system, [['role' => 'user', 'content' => $prompt]]),
            self::TEMPERATURE_JSON,
            ['type' => 'json_object'],
        );

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new AiException(502, $this->name().' returned malformed JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array<int,array{role:string,content:string}>
     */
    private function withSystem(?string $system, array $messages): array
    {
        if ($system !== null && $system !== '') {
            array_unshift($messages, ['role' => 'system', 'content' => $system]);
        }

        return $messages;
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     * @param  array<string,string>|null  $responseFormat
     */
    private function request(array $messages, float $temperature, ?array $responseFormat = null): string
    {
        if (! $this->configured()) {
            throw new AiException(503, $this->name().' API key is not configured.');
        }

        $config = config("services.{$this->configKey()}");
        $model = (string) $config['model'];
        $url = rtrim((string) $config['base_url'], '/').'/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }

        $request = Http::withToken((string) $config['api_key'])
            ->timeout(self::REQUEST_TIMEOUT_SECONDS);

        $response = $request->post($url, $payload);

        if ($response->serverError()) {
            usleep(self::RETRY_DELAY_MICROSECONDS);
            $response = $request->post($url, $payload);
        }

        if ($response->failed()) {
            Log::warning('[ai] '.$this->name().' request failed', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->json('error.message') ?? $response->body(),
            ]);

            throw new AiException($response->status(), $this->name().' request failed: '.$response->status());
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || $text === '') {
            Log::warning('[ai] '.$this->name().' returned an empty response', ['model' => $model]);

            throw new AiException(502, $this->name().' returned an empty response.');
        }

        return trim($text);
    }
}
