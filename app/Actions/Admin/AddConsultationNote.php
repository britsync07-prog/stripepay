<?php

namespace App\Actions\Admin;

use App\Enums\ContactChannel;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class AddConsultationNote
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        $data = $request->validate([
            'channel' => ['nullable', Rule::enum(ContactChannel::class)],
            'message' => ['required', 'string', 'max:2000'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        $channel = ContactChannel::tryFrom($data['channel'] ?? '') ?? ContactChannel::Note;
        $duration = $channel->isCall() ? ($data['duration'] ?? null) : null;

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => $channel->value,
                'message' => $data['message'],
                'duration' => $duration,
            ])
            ->log($channel === ContactChannel::Note ? 'Note added' : 'Contact logged');

        // Logging any note/contact means we've reached out — nudge the lead forward.
        AutoAdvanceLead::run($consultationRequest, LeadStage::Contacted, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $channel === ContactChannel::Note ? 'Note added.' : 'Contact logged.',
        ]);

        return back();
    }
}
