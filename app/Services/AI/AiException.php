<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * A failed AI provider call, carrying the upstream HTTP status so the app can
 * map it to a meaningful response (e.g. 429 rate-limit vs a generic outage).
 */
class AiException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}
