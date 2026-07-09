<?php

namespace App\Data\Lead;

use App\Models\ConsultationRequest;
use Spatie\LaravelData\Data;

class LeadData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public string $stage,
        public string $stage_label,
        public string $stage_tone,
        public bool $is_terminal,
        public ?string $name,
        public ?string $email,
        public ?string $company,
        public ?string $country,
        public ?string $title,
        public string $amount,
        public bool $unpriced,
        public ?string $source,
        public ?string $next_follow_up_at,
        public bool $follow_up_overdue,
        public ?string $lost_reason,
        public ?int $score,
        public ?string $score_band,
        public ?string $score_band_tone,
        public bool $is_paid,
        public string $created_at,
        public string $consultation_url,
    ) {}

    public static function fromModel(ConsultationRequest $c): self
    {
        return new self(
            uuid: $c->uuid,
            reference: $c->reference ?? strtoupper(substr($c->uuid, 0, 8)),
            stage: $c->lead_stage->value,
            stage_label: $c->lead_stage->label(),
            stage_tone: $c->lead_stage->badge(),
            is_terminal: $c->lead_stage->isTerminal(),
            name: $c->name,
            email: $c->email,
            company: $c->company,
            country: $c->country,
            title: $c->title,
            amount: $c->formattedAmount(),
            unpriced: (int) $c->amount <= 0,
            source: $c->lead_source,
            next_follow_up_at: $c->next_follow_up_at?->toDateString(),
            follow_up_overdue: $c->isFollowUpOverdue(),
            lost_reason: $c->lost_reason,
            score: $c->lead_score,
            score_band: $c->scoreBand()?->value,
            score_band_tone: $c->scoreBand()?->badge(),
            is_paid: $c->isPaid(),
            created_at: $c->created_at->toFormattedDayDateString(),
            consultation_url: route('admin.consultation', $c, false),
        );
    }
}
