<?php

namespace App\Enums;

/**
 * The priority band an AI lead score falls into. Thresholds and the badge tone
 * live here (not in the Action or the frontend) so a score maps to a colour in
 * exactly one place — the DTOs emit the tone, the UI never hardcodes it.
 */
enum LeadScoreBand: string
{
    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';

    private const HOT_THRESHOLD = 70;

    private const WARM_THRESHOLD = 40;

    public static function fromScore(?int $score): ?self
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= self::HOT_THRESHOLD => self::Hot,
            $score >= self::WARM_THRESHOLD => self::Warm,
            default => self::Cold,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Hot => 'Hot',
            self::Warm => 'Warm',
            self::Cold => 'Cold',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Hot => 'success',
            self::Warm => 'warning',
            self::Cold => 'outline',
        };
    }
}
