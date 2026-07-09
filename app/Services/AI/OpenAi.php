<?php

namespace App\Services\AI;

class OpenAi extends OpenAiCompatibleProvider
{
    protected function configKey(): string
    {
        return 'openai';
    }

    protected function name(): string
    {
        return 'OpenAI';
    }
}
