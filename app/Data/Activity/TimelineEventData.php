<?php

namespace App\Data\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

class TimelineEventData extends Data
{
    public function __construct(
        public int $id,
        public string $type,
        public ?string $message,
        public ?int $duration,
        public ?string $from,
        public ?string $to,
        public ?string $by,
        public ?string $at,
    ) {}

    /**
     * The activity timeline for a subject under a given log, newest first.
     *
     * @return Collection<int, self>
     */
    public static function forSubject(Model $subject, string $logName): Collection
    {
        return self::collect(
            Activity::where('log_name', $logName)
                ->whereMorphedTo('subject', $subject)
                ->with('causer')
                ->latest()
                ->get(),
            Collection::class,
        );
    }

    public static function fromModel(Activity $a): self
    {
        return new self(
            id: $a->id,
            type: $a->properties['type'] ?? 'note',
            message: $a->properties['message'] ?? null,
            duration: $a->properties['duration'] ?? null,
            from: $a->properties['from_label'] ?? null,
            to: $a->properties['to_label'] ?? null,
            by: $a->causer?->name,
            at: $a->created_at?->diffForHumans(),
        );
    }
}
