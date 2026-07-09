<?php

namespace App\Actions\Admin;

use App\Models\Project;
use App\Services\AI\Ai;
use App\Services\AI\AiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Turns a project's brief into a proposed milestone plan (titles + suggested
 * relative due dates) and appends them, so an admin doesn't build the delivery
 * plan from scratch. The milestones are ordinary editable records afterwards.
 */
class SuggestMilestones
{
    use AsAction;

    public function asController(Request $request, Project $project): RedirectResponse
    {
        if (! Ai::configured()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'AI assistance is not configured.']);

            return back();
        }

        try {
            $result = Ai::generateJson($this->prompt($project), $this->system(), $this->schema());
        } catch (AiException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $e->status === 429
                    ? 'The AI assistant is busy right now. Please try again shortly.'
                    : 'Could not draft a plan right now. Please try again.',
            ]);

            return back();
        }

        $created = $this->createMilestones($project, $result['milestones'] ?? []);
        $project->recomputeCompletion();

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'milestone', 'message' => "Drafted {$created} milestone(s) with AI"])
            ->log('Milestones drafted');

        Inertia::flash('toast', [
            'type' => $created > 0 ? 'success' : 'info',
            'message' => $created > 0
                ? "Added {$created} AI-drafted milestone".($created === 1 ? '' : 's').' — edit or remove any as needed.'
                : 'The assistant did not propose any milestones.',
        ]);

        return back();
    }

    /** @param  mixed  $milestones */
    private function createMilestones(Project $project, $milestones): int
    {
        if (! is_array($milestones)) {
            return 0;
        }

        $order = (int) ($project->milestones()->max('sort_order') ?? 0);
        $created = 0;

        foreach (array_slice($milestones, 0, 10) as $milestone) {
            $title = trim((string) ($milestone['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $days = min(730, max(0, (int) ($milestone['due_in_days'] ?? 0)));

            $project->milestones()->create([
                'title' => Str::limit($title, 255, ''),
                'description' => Str::limit(trim((string) ($milestone['description'] ?? '')), 2000, '') ?: null,
                'due_date' => now()->addDays($days)->toDateString(),
                'sort_order' => ++$order,
            ]);

            $created++;
        }

        return $created;
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You are a delivery lead at {$firm} planning a client engagement.",
            'Break the work into 3–6 concrete, sequential milestones a consultant would actually deliver.',
            'Each milestone: a short imperative title, a one-line description, and due_in_days (calendar days from today, increasing across the plan — e.g. 7, 14, 30).',
            'Base the plan only on the brief provided; do not invent scope beyond it.',
        ]);
    }

    private function prompt(Project $project): string
    {
        $project->loadMissing('consultationRequest', 'milestones');
        $brief = $project->consultationRequest?->message;
        $existing = $project->milestones->pluck('title')->implode(', ');

        $lines = array_filter([
            'Project: '.$project->title,
            $brief ? "Client brief:\n".$brief : 'Client brief: not provided — infer a sensible plan from the title.',
            $existing !== '' ? 'Milestones already planned (do not duplicate): '.$existing : null,
        ]);

        return "Draft a milestone plan for this engagement.\n\n".implode("\n\n", $lines);
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'milestones' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'description' => ['type' => 'STRING'],
                            'due_in_days' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['title', 'due_in_days'],
                    ],
                ],
            ],
            'required' => ['milestones'],
        ];
    }
}
