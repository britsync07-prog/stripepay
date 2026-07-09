<?php

namespace App\Data\Project;

use App\Models\Project;
use Spatie\LaravelData\Data;

/**
 * The AI delivery-health snapshot shared by the project page (initial state)
 * and the assess endpoint (fresh result), so the shape lives in one place.
 */
class ProjectHealthData extends Data
{
    public function __construct(
        public ?string $health,
        public ?string $health_label,
        public ?string $health_tone,
        public ?string $summary,
        public array $flags,
        public ?string $checked_at,
    ) {}

    public static function fromModel(Project $p): self
    {
        return new self(
            health: $p->health?->value,
            health_label: $p->health?->label(),
            health_tone: $p->health?->badge(),
            summary: $p->health_summary,
            flags: $p->health_flags ?? [],
            checked_at: $p->health_checked_at?->toFormattedDayDateString(),
        );
    }
}
