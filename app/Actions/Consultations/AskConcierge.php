<?php

namespace App\Actions\Consultations;

use App\Actions\Plans\GetActivePlans;
use App\Data\Plan\PlanData;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * A public, pre-sales concierge for guests on the landing site. It answers
 * questions about the firm's plans/services using ONLY the published plan data
 * (no private records, so nothing to isolate), stays strictly on-topic, and
 * nudges toward picking a plan or requesting a custom one. Rate-limited by IP
 * on the route since it's unauthenticated.
 */
class AskConcierge
{
    use AsAction;

    public function asController(Request $request): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'The assistant is unavailable right now.'], 503);
        }

        $validator = Validator::make($request->all(), [
            'messages' => ['required', 'array', 'min:1', 'max:16'],
            'messages.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'messages.*.content' => ['required', 'string', 'max:400'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please enter a question.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer = Ai::chat(
            $this->turns($validator->validated()['messages']),
            $this->system(),
        );

        return response()->json(['answer' => $answer]);
    }

    /**
     * Map the visitor-visible conversation to Gemini turns, dropping any leading
     * assistant turn — a conversation must start with the user.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array<int,array{role:string,text:string}>
     */
    private function turns(array $messages): array
    {
        $turns = array_map(fn (array $m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'text' => $m['content'],
        ], $messages);

        while (! empty($turns) && $turns[0]['role'] === 'model') {
            array_shift($turns);
        }

        return array_values($turns);
    }

    private function system(): string
    {
        $firm = config('app.name');

        $rules = implode(' ', [
            "You are the pre-sales concierge for {$firm}, a cross-border business advisory firm.",
            "Answer a visitor's questions about our services, plans and how to get started, using ONLY the plan information provided below.",
            'Never invent prices, features, timelines or guarantees that are not in the provided data.',
            "If the question is unrelated to {$firm}'s advisory services, politely decline and steer back to how we can help.",
            'If the visitor needs something no listed plan covers, suggest they request a custom plan.',
            'Keep replies short, warm and professional; end by inviting them to choose a plan or request a custom one. Plain text, no markdown headings.',
        ]);

        return $rules."\n\nHere are {$firm}'s current plans:\n\n".$this->plansContext();
    }

    private function plansContext(): string
    {
        $plans = GetActivePlans::run();

        if ($plans->isEmpty()) {
            return 'No plans are currently published — invite the visitor to request a custom plan.';
        }

        return $plans->map(function (PlanData $plan) {
            $lines = ['Plan: '.$plan->name.' — '.$plan->formatted_price.' ('.$plan->interval_label.')'];

            if ($plan->description) {
                $lines[] = '  '.$plan->description;
            }

            if ($plan->outcome) {
                $lines[] = '  Outcome: '.$plan->outcome;
            }

            foreach ($plan->features as $feature) {
                $lines[] = '  • '.$feature;
            }

            return implode("\n", $lines);
        })->implode("\n\n");
    }
}
