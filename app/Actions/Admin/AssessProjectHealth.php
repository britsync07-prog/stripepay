<?php

namespace App\Actions\Admin;

use App\Data\Project\ProjectHealthData;
use App\Enums\ProjectStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\AI\Ai;
use App\Services\AI\AiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Assesses a project's delivery health (on track / at risk / stalled) from its
 * milestones and timeline. Runs synchronously on demand, or as a queued job for
 * the "assess all" backfill — mirroring the ScoreLead pattern.
 */
class AssessProjectHealth
{
    use AsAction;

    private const MAX_RETRY_ATTEMPTS = 4;

    public function asController(Project $project): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        return response()->json($this->assess($project));
    }

    public function asJob(Project $project, int $attempt = 1): void
    {
        if (! Ai::configured() || $project->status !== ProjectStatus::Active) {
            return;
        }

        try {
            $this->assess($project);
        } catch (AiException $e) {
            if ($e->status === 429 && $attempt < self::MAX_RETRY_ATTEMPTS) {
                self::dispatch($project, $attempt + 1)->delay(now()->addSeconds(60 * $attempt));

                return;
            }

            Log::warning('[ai] project health check failed', [
                'project' => $project->id,
                'status' => $e->status,
                'attempt' => $attempt,
            ]);
        }
    }

    private function assess(Project $project): ProjectHealthData
    {
        $result = Ai::generateJson(
            $this->prompt($project),
            $this->system(),
            $this->schema(),
        );

        $health = in_array($result['health'] ?? null, ['on_track', 'at_risk', 'stalled'], true)
            ? $result['health']
            : 'at_risk';
        $summary = trim((string) ($result['summary'] ?? ''));
        $flags = array_values(array_filter(array_map(
            fn ($flag) => trim((string) $flag),
            is_array($result['flags'] ?? null) ? $result['flags'] : [],
        )));

        $project->forceFill([
            'health' => $health,
            'health_summary' => $summary,
            'health_flags' => $flags,
            'health_checked_at' => now(),
        ])->save();

        activity('project')
            ->causedBy(Auth::user())
            ->performedOn($project)
            ->withProperties([
                'type' => 'project',
                'message' => 'Assessed delivery health with AI: '.$health,
            ])
            ->log('Project health assessed');

        return ProjectHealthData::fromModel($project->refresh());
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You are a delivery lead at {$firm} reviewing whether a client project is on track.",
            'Classify its health as one of: on_track, at_risk, or stalled.',
            'Weigh: overall completion vs how long it has run, overdue milestones, milestones with no due date or stuck in progress, and whether a consultant is assigned.',
            'stalled = little/no progress or multiple overdue milestones; at_risk = slipping or missing dates; on_track = progressing with no red flags.',
            'Base the verdict only on the evidence provided; do not invent milestones or dates.',
            'Also give a one-sentence summary and up to 3 short flags (e.g. "2 milestones overdue", "No consultant assigned", "No due dates set").',
        ]);
    }

    private function prompt(Project $project): string
    {
        $state = implode("\n", $this->stateLines($project));

        return <<<TXT
        Assess this project's delivery health.

        {$state}
        TXT;
    }

    /** @return array<int,string> */
    private function stateLines(Project $project): array
    {
        $project->loadMissing('expert', 'milestones');

        $lines = [
            'Reference: '.$project->reference,
            'Title: '.$project->title,
            'Status: '.$project->status->label(),
            'Completion: '.$project->completion_percentage.'%',
            'Age: started '.$project->created_at->diffForHumans(),
            'Consultant: '.($project->expert?->name ?? 'none assigned'),
            'Milestones: '.$project->milestones->count(),
        ];

        foreach ($project->milestones->sortBy('sort_order') as $milestone) {
            $lines[] = '  - '.$milestone->title.': '.$milestone->status->label()
                .$this->dueSummary($milestone);
        }

        return $lines;
    }

    private function dueSummary(Milestone $milestone): string
    {
        if ($milestone->completed_at) {
            return ' (completed '.$milestone->completed_at->toDateString().')';
        }

        if (! $milestone->due_date) {
            return ' (no due date)';
        }

        return $milestone->due_date->isPast()
            ? ' (due '.$milestone->due_date->toDateString().' — OVERDUE)'
            : ' (due '.$milestone->due_date->toDateString().')';
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'health' => ['type' => 'STRING', 'enum' => ['on_track', 'at_risk', 'stalled']],
                'summary' => ['type' => 'STRING'],
                'flags' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['health', 'summary', 'flags'],
        ];
    }
}
