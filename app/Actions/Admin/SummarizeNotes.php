<?php

namespace App\Actions\Admin;

use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Cleans an admin's raw call/meeting notes into a concise summary, action items
 * and a suggested follow-up. A pure transform — it persists nothing; the admin
 * logs the result via the normal note form.
 */
class SummarizeNotes
{
    use AsAction;

    public function asController(Request $request): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please add some notes to summarize.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = Ai::generateJson(
            'Clean up these raw notes:'."\n\n".$validator->validated()['notes'],
            $this->system(),
            $this->schema(),
        );

        $followUp = (int) ($result['follow_up_in_days'] ?? 0);

        return response()->json([
            'summary' => trim((string) ($result['summary'] ?? '')),
            'action_items' => array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                is_array($result['action_items'] ?? null) ? $result['action_items'] : [],
            ))),
            'follow_up_in_days' => $followUp > 0 ? $followUp : null,
        ]);
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You tidy a {$firm} admin's raw call or meeting notes.",
            'Return a short factual summary (2–3 sentences), a list of clear action items, and follow_up_in_days (an integer number of days until the next follow-up is warranted, or 0 if none).',
            'Only use what is in the notes — do not invent commitments, names or dates.',
        ]);
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'summary' => ['type' => 'STRING'],
                'action_items' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
                'follow_up_in_days' => ['type' => 'INTEGER'],
            ],
            'required' => ['summary', 'action_items', 'follow_up_in_days'],
        ];
    }
}
