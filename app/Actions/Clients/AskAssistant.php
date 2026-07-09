<?php

namespace App\Actions\Clients;

use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * A client-portal support assistant. It answers a client's question strictly
 * from THEIR OWN account data — the context is built only from
 * $user->consultationRequests() (invoices/payments/projects all hang off those),
 * so a client can never reach another client's rows regardless of what they type.
 * The system prompt is a second line of defence, not the isolation boundary.
 */
class AskAssistant
{
    use AsAction;

    public function asController(Request $request): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'The assistant is unavailable right now.'], 503);
        }

        // Validate manually + return JSON: a thrown ValidationException on this
        // (non-api) route would render as an Inertia redirect, breaking the XHR.
        $validator = Validator::make($request->all(), [
            'messages' => ['required', 'array', 'min:1', 'max:20'],
            'messages.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'messages.*.content' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please enter a question.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer = Ai::chat(
            $this->turns($validator->validated()['messages']),
            $this->system($request->user()),
        );

        return response()->json(['answer' => $answer]);
    }

    /**
     * Map the client-visible conversation to Gemini turns and drop any leading
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

    private function system(User $user): string
    {
        $firm = config('app.name');

        $rules = implode(' ', [
            "You are the client-support assistant for {$firm}, a business advisory firm.",
            "Answer the client's questions using ONLY the account data provided below about their own engagements, invoices, payments and projects.",
            'Use their data verbatim — exact amounts, dates and statuses; never invent or estimate them.',
            "If the answer is not in the provided data, say you don't have that detail and invite them to contact the {$firm} team — do not guess.",
            'Never mention internal notes, other clients, or how the platform works behind the scenes.',
            'Keep replies short, warm and professional; plain text, no markdown headings.',
        ]);

        return $rules."\n\nHere is everything on record for THIS client's account:\n\n".$this->clientContext($user);
    }

    private function clientContext(User $user): string
    {
        $consultations = $user->consultationRequests()
            ->with('plan', 'expert', 'project', 'invoice', 'payments')
            ->latest()
            ->get();

        if ($consultations->isEmpty()) {
            return 'This client has no engagements, invoices, payments or projects on record yet.';
        }

        return $consultations
            ->map(fn (ConsultationRequest $c) => $this->engagementLines($c))
            ->implode("\n\n");
    }

    private function engagementLines(ConsultationRequest $c): string
    {
        $lines = [
            'Engagement '.$c->reference.': '.$c->offerTitle(),
            '  Payment status: '.$c->status->label().((int) $c->amount > 0 ? ' ('.$c->formattedAmount().')' : ''),
            '  Delivery status: '.$c->work_status->label(),
        ];

        if ($c->paid_at) {
            $lines[] = '  Paid on: '.$c->paid_at->toFormattedDayDateString();
        }

        if ($c->expert) {
            $lines[] = '  Consultant: '.$c->expert->name;
        }

        if ($c->project) {
            $lines[] = '  Project: '.$c->project->status->label().' — '.$c->project->completion_percentage.'% complete';
        }

        if ($c->invoice) {
            $lines[] = '  Invoice '.$c->invoice->reference.': '.$c->invoice->status->label()
                .' — total '.Invoice::formatMoneyMinorExact((int) $c->invoice->total, $c->invoice->currency);
        }

        foreach ($c->payments as $payment) {
            $lines[] = '  Payment '.($payment->reference ?? '').': '.$payment->formattedAmount()
                .' ('.$payment->status->label().')'
                .($payment->paid_at ? ' on '.$payment->paid_at->toFormattedDayDateString() : '');
        }

        return implode("\n", $lines);
    }
}
