<?php

namespace App\Data\Lead;

use App\Models\ConsultationRequest;
use Spatie\LaravelData\Data;

/**
 * The AI lead-score snapshot shared by the consultation page (initial state)
 * and the score endpoint (fresh result), so the shape lives in one place.
 */
class LeadScoreData extends Data
{
    public function __construct(
        public ?int $score,
        public ?string $band,
        public ?string $band_label,
        public ?string $band_tone,
        public ?string $summary,
        public array $flags,
        public ?string $scored_at,
    ) {}

    public static function fromModel(ConsultationRequest $c): self
    {
        $band = $c->scoreBand();

        return new self(
            score: $c->lead_score,
            band: $band?->value,
            band_label: $band?->label(),
            band_tone: $band?->badge(),
            summary: $c->lead_score_summary,
            flags: $c->lead_score_flags ?? [],
            scored_at: $c->lead_scored_at?->toFormattedDayDateString(),
        );
    }
}
