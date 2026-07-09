<?php

namespace App\Data\FollowUp;

use App\Data\Lead\LeadData;
use App\Models\ConsultationRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CalendarMonthData extends Data
{
    public function __construct(
        public string $month,
        public string $prev,
        public string $next,
        public array $weeks,
    ) {}

    public static function forMonth(CarbonInterface $month, Collection $leads): self
    {
        $monthStart = $month->copy()->startOfMonth();
        $gridStart = $monthStart->copy()->subDays($monthStart->dayOfWeek);
        $today = now()->startOfDay();

        $byDate = $leads->groupBy(
            fn (ConsultationRequest $c) => $c->next_follow_up_at->toDateString()
        );

        $weeks = collect(range(0, 5))->map(
            fn (int $week) => collect(range(0, 6))->map(
                fn (int $weekday) => self::day(
                    $gridStart->copy()->addDays($week * 7 + $weekday),
                    $monthStart,
                    $today,
                    $byDate,
                )
            )->all()
        )->all();

        return new self(
            month: $monthStart->translatedFormat('F Y'),
            prev: $monthStart->copy()->subMonth()->format('Y-m'),
            next: $monthStart->copy()->addMonth()->format('Y-m'),
            weeks: $weeks,
        );
    }

    private static function day(
        CarbonInterface $date,
        CarbonInterface $monthStart,
        CarbonInterface $today,
        Collection $byDate,
    ): CalendarDayData {
        $iso = $date->toDateString();

        return new CalendarDayData(
            iso: $iso,
            day: $date->day,
            inMonth: $date->isSameMonth($monthStart),
            isToday: $date->isSameDay($today),
            isPast: $date->lt($today),
            leads: $byDate->get($iso, collect())
                ->map(fn (ConsultationRequest $c) => LeadData::fromModel($c))
                ->values()
                ->all(),
        );
    }
}
