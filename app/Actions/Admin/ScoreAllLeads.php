<?php

namespace App\Actions\Admin;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Services\AI\Ai;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Queues an AI score for every open lead that hasn't been scored yet, so the
 * board becomes a ranked queue without the admin clicking each lead. Terminal
 * (Won/Lost) and already-scored leads are skipped.
 */
class ScoreAllLeads
{
    use AsAction;

    /** Bound the fan-out so one click can't flood the free-tier AI quota. */
    private const BATCH_CAP = 50;

    /** Space the queued jobs out so they don't all hit the API in the same minute. */
    private const STAGGER_SECONDS = 6;

    public function asController(): RedirectResponse
    {
        if (! Ai::configured()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'AI assistance is not configured.']);

            return back();
        }

        $query = ConsultationRequest::query()
            ->leads()
            ->whereNull('lead_score')
            ->whereNotIn('lead_stage', [LeadStage::Won->value, LeadStage::Lost->value]);

        $pending = (int) $query->count();
        $leads = $query->oldest('next_follow_up_at')->limit(self::BATCH_CAP)->get();

        // Stagger dispatch so the batch stays under the per-minute API limit;
        // each job also backs off on its own if it still hits a rate limit.
        $leads->values()->each(
            fn (ConsultationRequest $lead, int $i) => ScoreLead::dispatch($lead)
                ->delay(now()->addSeconds($i * self::STAGGER_SECONDS)),
        );

        Inertia::flash('toast', [
            'type' => $leads->isEmpty() ? 'info' : 'success',
            'message' => $this->message($leads->count(), $pending),
        ]);

        return back();
    }

    private function message(int $queued, int $pending): string
    {
        if ($queued === 0) {
            return 'Every open lead is already scored.';
        }

        $remaining = $pending - $queued;

        return 'Scoring '.$queued.' lead'.($queued === 1 ? '' : 's')
            .' — scores appear on the board as they are calculated.'
            .($remaining > 0 ? " Run again for the remaining {$remaining}." : '');
    }
}
