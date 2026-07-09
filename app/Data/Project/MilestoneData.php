<?php

namespace App\Data\Project;

use App\Models\Milestone;
use Spatie\LaravelData\Data;

class MilestoneData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public string $status,
        public string $status_label,
        public int $sort_order,
        public ?string $due_date,
        public ?string $due_on,
        public ?string $completed_at,
        /** @var array<int, array{value: string, label: string}> */
        public array $status_options,
    ) {}

    public static function fromModel(Milestone $m): self
    {
        return new self(
            id: $m->id,
            title: $m->title,
            description: $m->description,
            status: $m->status->value,
            status_label: $m->status->label(),
            sort_order: $m->sort_order,
            due_date: $m->due_date?->toFormattedDayDateString(),
            due_on: $m->due_date?->toDateString(),
            completed_at: $m->completed_at?->toFormattedDayDateString(),
            status_options: $m->status->transitionOptions(),
        );
    }
}
