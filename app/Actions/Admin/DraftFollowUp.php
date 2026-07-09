<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Activitylog\Models\Activity;

/**
 * Drafts the chase email for a lead that is due a follow-up, grounded in the
 * lead's actual history — stage, what was quoted, when we last spoke and what
 * was said — so the admin sends a chaser that picks up the thread instead of
 * a generic "just checking in".
 */
class DraftFollowUp
{
    use AsAction;

    private const HISTORY_LIMIT = 12;

    public function asController(ConsultationRequest $consultationRequest): JsonResponse
    {
        abort_unless($consultationRequest->isLead(), 404);

        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        $result = Ai::generateJson(
            $this->prompt($consultationRequest),
            $this->system(),
            $this->schema(),
        );

        return response()->json([
            'subject' => trim((string) ($result['subject'] ?? '')),
            'body' => trim((string) ($result['body'] ?? '')),
        ]);
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You write follow-up emails to prospects on behalf of the {$firm} team.",
            'Write the chaser for THIS lead from its history: reference what was already discussed or quoted, and move toward one clear next step.',
            'Warm, professional, concise — under 120 words, one call to action.',
            'Start with a greeting using the prospect\'s name if known.',
            'Never use bracketed placeholders, never invent dates, prices, or conversations that are not in the history.',
            "Sign off simply as the {$firm} team.",
        ]);
    }

    private function prompt(ConsultationRequest $c): string
    {
        $state = array_filter([
            'Prospect: '.($c->name ?: 'unknown'),
            $c->company ? 'Company: '.$c->company : null,
            'Service discussed: '.$c->offerTitle(),
            'Pipeline stage: '.$c->lead_stage->label(),
            'Price: '.($c->isUnpriced() ? 'not quoted yet' : $c->formattedAmount().' (quoted)'),
            $c->next_follow_up_at
                ? 'Follow-up was scheduled for '.$c->next_follow_up_at->toDateString().($c->isFollowUpOverdue() ? ' (now overdue)' : '')
                : null,
            $c->message ? "What they originally asked for:\n".$c->message : null,
        ]);

        $history = $this->historyLines($c);

        return "Draft the follow-up email for this lead.\n\n".implode("\n", $state)
            ."\n\nRecent history (newest first):\n"
            .($history !== '' ? $history : '  (no contacts logged yet — this is the first follow-up)');
    }

    private function historyLines(ConsultationRequest $c): string
    {
        return Activity::whereIn('log_name', ['consultation', 'lead'])
            ->whereMorphedTo('subject', $c)
            ->latest()
            ->take(self::HISTORY_LIMIT)
            ->get()
            ->map(function (Activity $a) {
                $type = $a->properties['type'] ?? 'note';
                $message = $a->properties['message'] ?? $a->description;

                return '  - ['.$a->created_at->toDateString().'] '.$type.': '.$message;
            })
            ->implode("\n");
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'subject' => ['type' => 'STRING'],
                'body' => ['type' => 'STRING'],
            ],
            'required' => ['subject', 'body'],
        ];
    }
}
