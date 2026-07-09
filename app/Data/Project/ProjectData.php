<?php

namespace App\Data\Project;

use App\Models\Project;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public string $title,
        public ?string $client,
        public ?string $expert,
        public ?string $consultation_uuid,
        public ?string $consultation_reference,
        public string $status,
        public string $status_label,
        public string $status_tone,
        public int $completion_percentage,
        public ?string $created_at,
        /** @var array<int, MilestoneData> */
        public array $milestones,
    ) {}

    public static function fromModel(Project $p): self
    {
        return new self(
            uuid: $p->uuid,
            reference: $p->reference ?? strtoupper(substr($p->uuid, 0, 8)),
            title: $p->title,
            client: $p->client?->name,
            expert: $p->expert?->name,
            consultation_uuid: $p->consultationRequest?->uuid,
            consultation_reference: $p->consultationRequest?->reference,
            status: $p->status->value,
            status_label: $p->status->label(),
            status_tone: $p->status->badge(),
            completion_percentage: $p->completion_percentage,
            created_at: $p->created_at?->toFormattedDayDateString(),
            milestones: $p->milestones->map(fn ($m) => MilestoneData::fromModel($m))->all(),
        );
    }
}
