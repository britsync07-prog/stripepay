<?php

namespace App\Data\FollowUp;

use Spatie\LaravelData\Data;

class CalendarDayData extends Data
{
    public function __construct(
        public string $iso,
        public int $day,
        public bool $inMonth,
        public bool $isToday,
        public bool $isPast,
        public array $leads,
    ) {}
}
