<?php

namespace App\Data\FollowUp;

use App\Data\Lead\LeadData;
use App\Models\ConsultationRequest;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class FollowUpBoardData extends Data
{
    public function __construct(
        public array $overdue,
        public array $today,
        public array $upcoming,
        public array $stats,
    ) {}

    public static function fromLeads(Collection $leads): self
    {
        $buckets = $leads->groupBy(fn (ConsultationRequest $c) => $c->followUpBucket());

        $section = fn (string $key) => $buckets->get($key, collect())
            ->map(fn (ConsultationRequest $c) => LeadData::fromModel($c))
            ->values()
            ->all();

        return new self(
            overdue: $section('overdue'),
            today: $section('today'),
            upcoming: $section('upcoming'),
            stats: [
                'overdue' => $buckets->get('overdue')?->count() ?? 0,
                'today' => $buckets->get('today')?->count() ?? 0,
                'upcoming' => $buckets->get('upcoming')?->count() ?? 0,
            ],
        );
    }
}
