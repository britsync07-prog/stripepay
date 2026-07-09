<?php

namespace App\Actions\Admin;

use App\Data\FollowUp\CalendarMonthData;
use App\Data\FollowUp\FollowUpBoardData;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ListFollowUps
{
    use AsAction;

    public function asController(Request $request)
    {
        $leads = ConsultationRequest::openFollowUps()
            ->orderBy('next_follow_up_at')
            ->get();

        $board = fn () => once(fn () => FollowUpBoardData::fromLeads($leads));

        $monthParam = (string) $request->query('month');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)
            ? Carbon::createFromFormat('!Y-m', $monthParam)
            : now()->startOfMonth();

        return Inertia::render('admin/follow-ups', [
            'overdue' => fn () => $board()->overdue,
            'today' => fn () => $board()->today,
            'upcoming' => fn () => $board()->upcoming,
            'stats' => fn () => $board()->stats,
            'calendar' => fn () => CalendarMonthData::forMonth($month, $leads),
        ]);
    }
}
