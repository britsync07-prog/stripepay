<?php

namespace App\Services\AI;

/**
 * A pluggable LLM backend. Concrete drivers (Gemini, Groq) implement this; the
 * active one is chosen by config('services.ai.default') and resolved via the
 * Ai entry point. Failures throw AiException carrying the upstream HTTP status.
 */
interface AiProvider
{
    public function configured(): bool;

    public function generate(string $prompt, ?string $system = null): string;

    /**
     * @param  array<string,mixed>  $schema
     * @return array<string,mixed>
     */
    public function generateJson(string $prompt, ?string $system, array $schema): array;

    /**
     * Multi-turn conversation. Each turn is ['role' => 'user'|'model', 'text' => ...].
     *
     * @param  array<int,array{role:string,text:string}>  $turns
     */
    public function chat(array $turns, ?string $system = null): string;
}
