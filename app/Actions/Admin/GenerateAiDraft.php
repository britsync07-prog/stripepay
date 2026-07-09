<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateAiDraft
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kind' => ['required', Rule::in(['proposal', 'email'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $kind = (string) $validator->validated()['kind'];

        if (! Ai::configured()) {
            return response()->json(['message' => 'AI drafting is not configured.'], 503);
        }

        $draft = Ai::generate(
            $this->prompt($consultationRequest, $kind),
            $this->system($kind),
        );

        activity('consultation')
            ->causedBy(Auth::user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'note',
                'message' => 'Drafted a '.$kind.' with AI',
            ])
            ->log('AI draft generated');

        return response()->json(['draft' => $draft]);
    }

    private function system(string $kind): string
    {
        $firm = config('app.name');

        $rules = implode(' ', [
            "You are a senior consultant at {$firm}, a cross-border business advisory firm.",
            'Never use bracketed placeholders such as [Your Name], [Date], or [Company] — write finished copy only.',
            'Never invent a date, address, or letterhead block (To/From/Date/Subject); the platform adds delivery details.',
            'Never invent a person\'s name to sign off — sign off simply as the '.$firm.' team.',
            'Do not fabricate facts, figures, or credentials that were not provided.',
        ]);

        return $kind === 'proposal'
            ? "{$rules} Write a tight, skimmable consulting proposal in Markdown, 200 words or fewer. Open with a short title, then these sections: Understanding (1-2 sentences), Approach (3-4 short bullets), Deliverables (3-4 short bullets), Next steps (1-2 sentences). Use plain, direct language. Keep bullets to one line each. No marketing adjectives, no filler, no repetition — every line must add information."
            : "{$rules} Write a short, warm, professional follow-up email to the prospect. Start with the greeting line (no subject/header block). Keep it under 120 words, plain and direct, and end with a clear call to action.";
    }

    private function prompt(ConsultationRequest $c, string $kind): string
    {
        $country = $c->countryName();

        $lines = array_filter([
            'Prospect: '.($c->name ?: 'Unknown'),
            $c->company ? 'Company: '.$c->company : null,
            $country ? 'Country: '.$country : null,
            'Service: '.$c->offerTitle(),
            (int) $c->amount > 0 ? 'Budget/price: '.$c->formattedAmount() : null,
            $c->message ? "What they asked for:\n".$c->message : null,
        ]);

        $context = implode("\n", $lines);

        return $kind === 'proposal'
            ? "Draft a consulting proposal for this prospect based on the details below.\n\n{$context}"
            : "Draft a follow-up email to this prospect based on the details below.\n\n{$context}";
    }
}
