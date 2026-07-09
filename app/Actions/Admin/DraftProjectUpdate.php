<?php

namespace App\Actions\Admin;

use App\Models\Milestone;
use App\Models\Project;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Drafts a client-facing progress update from the project's real milestone
 * state — what's done, what's in motion, what's next — so an admin can send a
 * polished status email without composing it from the board.
 */
class DraftProjectUpdate
{
    use AsAction;

    public function asController(Project $project): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        $draft = Ai::generate($this->prompt($project), $this->system());

        return response()->json(['draft' => $draft]);
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You write progress-update emails to clients on behalf of the {$firm} team.",
            'Summarize the engagement status from the milestone facts provided: completed work first, then what is in progress, then what comes next.',
            'Warm, confident, plain language — under 150 words. If something is overdue, frame it honestly as being worked on; never hide it and never over-promise a date.',
            'Use only the facts provided. Never use bracketed placeholders and never invent milestones, dates or results.',
            "Start with a greeting using the client's name if known, and sign off simply as the {$firm} team.",
        ]);
    }

    private function prompt(Project $project): string
    {
        $project->loadMissing('consultationRequest', 'milestones');

        $milestones = $project->milestones
            ->sortBy('sort_order')
            ->map(fn (Milestone $m) => '  - '.$m->title.' — '.$m->status->label().$this->dueSummary($m))
            ->implode("\n");

        $lines = array_filter([
            'Client: '.($project->consultationRequest?->name ?: 'unknown'),
            'Engagement: '.$project->title,
            'Overall completion: '.$project->completion_percentage.'%',
            'Status: '.$project->status->label(),
        ]);

        return "Draft a progress update for this client.\n\n".implode("\n", $lines)
            ."\n\nMilestones:\n".($milestones !== '' ? $milestones : '  (no milestones planned yet)');
    }

    private function dueSummary(Milestone $m): string
    {
        if ($m->completed_at) {
            return ' (completed '.$m->completed_at->toDateString().')';
        }

        if (! $m->due_date) {
            return '';
        }

        return $m->due_date->isPast()
            ? ' (due '.$m->due_date->toDateString().' — overdue)'
            : ' (due '.$m->due_date->toDateString().')';
    }
}
