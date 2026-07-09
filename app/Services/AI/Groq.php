<?php

namespace App\Services\AI;

class Groq extends OpenAiCompatibleProvider
{
    protected function configKey(): string
    {
        return 'groq';
    }

    protected function name(): string
    {
        return 'Groq';
    }
}
