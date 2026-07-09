<?php

namespace App\Actions\Admin;

use App\Data\Lead\LeadScoreData;
use App\Models\ConsultationRequest;
use App\Services\AI\Ai;
use App\Services\AI\AiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class ScoreLead
{
    use AsAction;

    private const MAX_RETRY_ATTEMPTS = 4;

    /** On-demand scoring from the consultation page — synchronous for instant feedback. */
    public function asController(ConsultationRequest $consultationRequest): JsonResponse
    {
        abort_unless($consultationRequest->isLead(), 404);

        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        return response()->json($this->score($consultationRequest));
    }

    /**
     * Background scoring (auto-on-inbound + "score all" backfill). Skips quietly
     * when not applicable, and re-queues with backoff on a rate limit rather
     * than hammering the API — mirrors the BackfillStripeFee retry pattern.
     */
    public function asJob(ConsultationRequest $consultationRequest, int $attempt = 1): void
    {
        if (! $consultationRequest->isLead()
            || $consultationRequest->lead_stage->isTerminal()
            || ! Ai::configured()) {
            return;
        }

        try {
            $this->score($consultationRequest);
        } catch (AiException $e) {
            if ($e->status === 429 && $attempt < self::MAX_RETRY_ATTEMPTS) {
                self::dispatch($consultationRequest, $attempt + 1)->delay(now()->addSeconds(60 * $attempt));

                return;
            }

            Log::warning('[ai] lead auto-score failed', [
                'lead' => $consultationRequest->id,
                'status' => $e->status,
                'attempt' => $attempt,
            ]);
        }
    }

    private function score(ConsultationRequest $consultationRequest): LeadScoreData
    {
        $result = Ai::generateJson(
            $this->prompt($consultationRequest),
            $this->system(),
            $this->schema(),
        );

        $score = max(0, min(100, (int) ($result['score'] ?? 0)));
        $summary = trim((string) ($result['summary'] ?? ''));
        $flags = array_values(array_filter(array_map(
            fn ($flag) => trim((string) $flag),
            is_array($result['flags'] ?? null) ? $result['flags'] : [],
        )));

        $consultationRequest->forceFill([
            'lead_score' => $score,
            'lead_score_summary' => $summary,
            'lead_score_flags' => $flags,
            'lead_scored_at' => now(),
        ])->save();

        activity('lead')
            ->causedBy(Auth::user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'lead',
                'message' => 'Scored the lead with AI ('.$score.'/100)',
            ])
            ->log('Lead scored');

        return LeadScoreData::fromModel($consultationRequest);
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You are a revenue operations analyst at {$firm}, a cross-border business advisory firm.",
            'Score how likely this lead is to convert into a paid engagement, from 0 (dead) to 100 (ready to pay).',
            'Weigh: how far along the pipeline stage is, whether a price is set, budget size, how engaged and reachable the prospect is, follow-up health (overdue is bad), and how concrete their described need is.',
            'A Won lead is already converted — score it 100. A Lost lead is dead — score it low.',
            'Base the score only on the evidence provided; do not invent facts.',
            'Also return a one-sentence summary of the score, and up to 3 short risk or opportunity flags (e.g. "Follow-up overdue", "No budget set", "High-value enterprise").',
        ]);
    }

    private function prompt(ConsultationRequest $c): string
    {
        $state = implode("\n", $this->stateLines($c));

        return <<<TXT
        Score this sales lead's likelihood to convert into a paid engagement.

        {$state}
        TXT;
    }

    /** @return array<int,string> */
    private function stateLines(ConsultationRequest $c): array
    {
        return array_values(array_filter([
            'Pipeline stage: '.$c->lead_stage->label(),
            'Lead source: '.($c->lead_source ?? 'unknown').($c->lead_source === 'manual' ? ' (added by an admin)' : ' (inbound request)'),
            'Prospect: '.($c->name ?: 'unknown (no name captured)'),
            $c->company ? 'Company: '.$c->company : 'Company: not given',
            $c->countryName() ? 'Country: '.$c->countryName() : null,
            'Contactable by: '.$c->contactSummary(),
            'Price: '.($c->isUnpriced() ? 'NOT SET YET' : $c->formattedAmount()),
            'Payment: '.$c->status->label().($c->isPaid() ? ' (already paid)' : ''),
            $c->next_follow_up_at
                ? 'Next follow-up: '.$c->next_follow_up_at->toDateString().($c->isFollowUpOverdue() ? ' (OVERDUE)' : '')
                : 'Next follow-up: none scheduled',
            $c->lost_reason ? 'Lost reason: '.$c->lost_reason : null,
            'Age: submitted '.$c->created_at->diffForHumans(),
            $c->message ? "What they asked for:\n".$c->message : 'What they asked for: nothing written',
        ]));
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'summary' => ['type' => 'STRING'],
                'flags' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['score', 'summary', 'flags'],
        ];
    }
}
