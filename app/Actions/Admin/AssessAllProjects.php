<?php

namespace App\Actions\Admin;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\AI\Ai;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Queues an AI health assessment for every active project, staggered and capped
 * to respect the API quota (mirrors ScoreAllLeads).
 */
class AssessAllProjects
{
    use AsAction;

    private const BATCH_CAP = 50;

    private const STAGGER_SECONDS = 6;

    public function asController(): RedirectResponse
    {
        if (! Ai::configured()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'AI assistance is not configured.']);

            return back();
        }

        $query = Project::query()->where('status', ProjectStatus::Active->value);

        $pending = (int) $query->count();
        $projects = $query->oldest('health_checked_at')->limit(self::BATCH_CAP)->get();

        $projects->values()->each(
            fn (Project $project, int $i) => AssessProjectHealth::dispatch($project)
                ->delay(now()->addSeconds($i * self::STAGGER_SECONDS)),
        );

        Inertia::flash('toast', [
            'type' => $projects->isEmpty() ? 'info' : 'success',
            'message' => $this->message($projects->count(), $pending),
        ]);

        return back();
    }

    private function message(int $queued, int $pending): string
    {
        if ($queued === 0) {
            return 'There are no active projects to assess.';
        }

        $remaining = $pending - $queued;

        return 'Assessing '.$queued.' project'.($queued === 1 ? '' : 's')
            .' — health appears on the board as each check finishes.'
            .($remaining > 0 ? " Run again for the remaining {$remaining}." : '');
    }
}
