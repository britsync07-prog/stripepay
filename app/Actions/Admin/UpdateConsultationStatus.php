<?php

namespace App\Actions\Admin;

use App\Enums\ConsultationWorkStatus;
use App\Events\ConsultationStatusChanged;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateConsultationStatus
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        $data = $request->validate([
            'work_status' => ['required', Rule::enum(ConsultationWorkStatus::class)],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $from = $consultationRequest->work_status;
        $to = ConsultationWorkStatus::from($data['work_status']);

        if ($to === $from) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => "This consultation is already {$to->label()}.",
            ]);

            return back();
        }

        if ($from !== null && ! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'work_status' => "Can't move from {$from->label()} to {$to->label()}.",
            ]);
        }

        $attributes = ['work_status' => $to];
        if ($to === ConsultationWorkStatus::Resolved) {
            $attributes['resolved_at'] = $consultationRequest->resolved_at ?? now();
        } elseif ($to === ConsultationWorkStatus::Closed) {
            $attributes['closed_at'] = now();
        } elseif ($to === ConsultationWorkStatus::InProgress) {
            $attributes['resolved_at'] = null;
            $attributes['closed_at'] = null;
        }

        $consultationRequest->update($attributes);

        ConsultationStatusChanged::dispatch($consultationRequest, $to, $from);

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'status',
                'from' => $from?->value,
                'to' => $to->value,
                'from_label' => $from?->label(),
                'to_label' => $to->label(),
                'message' => $data['message'] ?? null,
            ])
            ->log('Status changed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return back();
    }
}
