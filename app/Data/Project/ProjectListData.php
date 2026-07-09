<?php

namespace App\Data\Project;

use App\Models\Project;
use Spatie\LaravelData\Data;

class ProjectListData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public string $title,
        public ?string $client,
        public ?string $expert,
        public string $status,
        public string $status_label,
        public string $status_tone,
        public int $completion_percentage,
        public int $milestones_count,
        public ?string $health,
        public ?string $health_label,
        public ?string $health_tone,
        public ?string $created_at,
    ) {}

    /** Portal-facing shape — the internal AI health verdict is never sent to clients. */
    public static function fromModelForClient(Project $p): self
    {
        return self::fromModel($p)->except('health', 'health_label', 'health_tone');
    }

    public static function fromModel(Project $p): self
    {
        return new self(
            uuid: $p->uuid,
            reference: $p->reference ?? strtoupper(substr($p->uuid, 0, 8)),
            title: $p->title,
            client: $p->client?->name,
            expert: $p->expert?->name,
            status: $p->status->value,
            status_label: $p->status->label(),
            status_tone: $p->status->badge(),
            completion_percentage: $p->completion_percentage,
            milestones_count: (int) ($p->milestones_count ?? $p->milestones()->count()),
            health: $p->health?->value,
            health_label: $p->health?->label(),
            health_tone: $p->health?->badge(),
            created_at: $p->created_at?->toFormattedDayDateString(),
        );
    }
}
