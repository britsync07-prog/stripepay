<?php

namespace App\Actions\Admin;

use App\Data\Project\ProjectListData;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListProjects
{
    use AsAction;

    public function asController(Request $request)
    {
        $projects = QueryBuilder::for(Project::class)
            ->with('client', 'expert')
            ->withCount('milestones')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhere('title', 'like', "%{$value}%")
                            ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('expert', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/projects', [
            'projects' => $projects->through(fn (Project $p) => ProjectListData::fromModel($p)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => ProjectStatus::options(),
            'stats' => [
                'total' => Project::count(),
                'active' => Project::where('status', ProjectStatus::Active->value)->count(),
                'completed' => Project::where('status', ProjectStatus::Completed->value)->count(),
            ],
        ]);
    }
}
