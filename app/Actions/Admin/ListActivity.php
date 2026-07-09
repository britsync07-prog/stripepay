<?php

namespace App\Actions\Admin;

use App\Data\Activity\ActivityLogData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** The admin audit trail — a read-only, filterable view of the activity log. */
class ListActivity
{
    use AsAction;

    public function asController(Request $request)
    {
        $activities = QueryBuilder::for(Activity::class)
            ->with(['causer', 'subject'])
            ->allowedFilters(
                AllowedFilter::exact('log_name'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $escaped = '%'.addcslashes($value, '%_\\').'%';
                    $query->where('description', 'like', $escaped);
                }),
            )
            ->defaultSort('-id')
            ->paginate(20)
            ->withQueryString();

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->map(fn (string $name) => ['value' => $name, 'label' => ucfirst($name)])
            ->values();

        return Inertia::render('admin/activity', [
            'activities' => $activities->through(fn (Activity $a) => ActivityLogData::fromModel($a)),
            'logNames' => $logNames,
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'log_name' => (string) $request->input('filter.log_name', ''),
            ],
            'stats' => [
                'total' => Activity::count(),
                'today' => Activity::whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
