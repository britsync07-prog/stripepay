<?php

namespace App\Actions\Clients;

use App\Data\ConsultationRequest\ConsultationData;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientConsultations
{
    use AsAction;

    public function asController(Request $request)
    {
        $consultations = $request->user()
            ->consultationRequests()
            ->with('plan', 'project', 'invoice')
            ->latest()
            ->get()
            ->map(fn (ConsultationRequest $c) => ConsultationData::fromModelForClient($c));

        return Inertia::render('portal/consultations', [
            'consultations' => $consultations,
        ]);
    }
}
