<?php

namespace Tests\Unit;

use App\Data\FollowUp\CalendarMonthData;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarMonthDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_builds_a_sunday_first_6x7_grid_with_correct_flags(): void
    {
        Carbon::setTestNow('2026-07-15 10:00:00');

        $cal = CalendarMonthData::forMonth(Carbon::parse('2026-07-01'), collect());

        $this->assertSame('July 2026', $cal->month);
        $this->assertSame('2026-06', $cal->prev);
        $this->assertSame('2026-08', $cal->next);

        $this->assertCount(6, $cal->weeks);
        $this->assertCount(7, $cal->weeks[0]);

        $first = $cal->weeks[0][0];
        $this->assertSame(0, Carbon::parse($first->iso)->dayOfWeek, 'grid starts on a Sunday');
        $this->assertTrue(Carbon::parse($first->iso)->lte(Carbon::parse('2026-07-01')));
        $this->assertFalse($first->inMonth);

        $days = collect($cal->weeks)->collapse();

        $today = $days->firstWhere('iso', '2026-07-15');
        $this->assertTrue($today->isToday);
        $this->assertTrue($today->inMonth);
        $this->assertFalse($today->isPast);

        $yesterday = $days->firstWhere('iso', '2026-07-14');
        $this->assertFalse($yesterday->isToday);
        $this->assertTrue($yesterday->isPast);
    }

    public function test_it_always_produces_a_full_42_day_grid(): void
    {
        Carbon::setTestNow('2026-02-10 09:00:00');

        $cal = CalendarMonthData::forMonth(Carbon::parse('2026-02-01'), collect());

        $this->assertSame('February 2026', $cal->month);
        $this->assertSame(42, collect($cal->weeks)->collapse()->count());
    }
}
