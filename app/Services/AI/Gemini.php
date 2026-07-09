<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Gemini implements AiProvider
{
    /** Prose (drafts) wants some flair; structured data wants determinism. */
    private const TEMPERATURE_TEXT = 0.7;

    private const TEMPERATURE_JSON = 0.3;

    /** Retry once on a transient 5xx, pausing this long between attempts. */
    private const RETRY_DELAY_MICROSECONDS = 400_000;

    private const REQUEST_TIMEOUT_SECONDS = 60;

    public function configured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    public function generate(string $prompt, ?string $system = null): string
    {
        return $this->request(
            [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            $system,
            ['temperature' => self::TEMPERATURE_TEXT],
        );
    }

    /**
     * @param  array<int,array{role:string,text:string}>  $turns
     */
    public function chat(array $turns, ?string $system = null): string
    {
        $contents = array_map(
            fn (array $turn) => ['role' => $turn['role'], 'parts' => [['text' => $turn['text']]]],
            $turns,
        );

        return $this->request($contents, $system, ['temperature' => self::TEMPERATURE_TEXT]);
    }

    /**
     * @param  array<string,mixed>  $schema
     * @return array<string,mixed>
     */
    public function generateJson(string $prompt, ?string $system, array $schema): array
    {
        $text = $this->request(
            [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            $system,
            [
                'temperature' => self::TEMPERATURE_JSON,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        );

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new AiException(502, 'Gemini returned malformed JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<int,array{role:string,parts:array<int,array{text:string}>}>  $contents
     * @param  array<string,mixed>  $generationConfig
     */
    private function request(array $contents, ?string $system, array $generationConfig): string
    {
        if (! $this->configured()) {
            throw new AiException(503, 'Gemini API key is not configured.');
        }

        $model = (string) config('services.gemini.model');
        $url = rtrim((string) config('services.gemini.base_url'), '/')."/models/{$model}:generateContent";

        $payload = [
            'contents' => $contents,
            'generationConfig' => $generationConfig,
        ];

        if ($system !== null && $system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        $request = Http::withHeaders([
            'x-goog-api-key' => (string) config('services.gemini.api_key'),
        ])->timeout(self::REQUEST_TIMEOUT_SECONDS);

        $response = $request->post($url, $payload);

        // Retry once on a 5xx (Gemini "model overloaded" is transient), but never
        // on a 4xx — a 429 means wait, and retrying only wastes quota.
        if ($response->serverError()) {
            usleep(self::RETRY_DELAY_MICROSECONDS);
            $response = $request->post($url, $payload);
        }

        if ($response->failed()) {
            Log::warning('[ai] Gemini request failed', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->json('error.message') ?? $response->body(),
            ]);

            throw new AiException($response->status(), 'Gemini request failed: '.$response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            Log::warning('[ai] Gemini returned an empty response', ['model' => $model]);

            throw new AiException(502, 'Gemini returned an empty response.');
        }

        return trim($text);
    }
}
