<?php

namespace App\Services\AI;

/**
 * The single entry point every feature uses for AI. It delegates to whichever
 * provider is configured as the default (config('services.ai.default') —
 * "groq" or "gemini"), so switching providers is a one-line config/env change
 * and no call site changes.
 */
class Ai
{
    public static function provider(): AiProvider
    {
        return app(AiProvider::class);
    }

    public static function configured(): bool
    {
        return self::provider()->configured();
    }

    public static function generate(string $prompt, ?string $system = null): string
    {
        return self::provider()->generate($prompt, $system);
    }

    /**
     * @param  array<string,mixed>  $schema
     * @return array<string,mixed>
     */
    public static function generateJson(string $prompt, ?string $system, array $schema): array
    {
        return self::provider()->generateJson($prompt, $system, $schema);
    }

    /**
     * @param  array<int,array{role:string,text:string}>  $turns
     */
    public static function chat(array $turns, ?string $system = null): string
    {
        return self::provider()->chat($turns, $system);
    }
}
