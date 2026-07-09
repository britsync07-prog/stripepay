<?php

namespace App\Enums;

/**
 * The AI-assessed delivery health of a project. The badge tone lives here so a
 * verdict maps to a colour in one place; the DTOs emit the tone to the frontend.
 */
enum ProjectHealth: string
{
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Stalled = 'stalled';

    public function label(): string
    {
        return match ($this) {
            self::OnTrack => 'On track',
            self::AtRisk => 'At risk',
            self::Stalled => 'Stalled',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::OnTrack => 'success',
            self::AtRisk => 'warning',
            self::Stalled => 'destructive',
        };
    }
}
