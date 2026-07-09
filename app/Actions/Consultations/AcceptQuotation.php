<?php

namespace App\Actions\Consultations;

use App\Models\ConsultationRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The client confirms their "Prepared for" details on a quotation and proceeds
 * to pay. We only update the request's contact fields (company stays optional)
 * and re-key the CRM contact — no account is created or linked here; identity is
 * provisioned only on payment, exactly like the intake paths.
 */
class AcceptQuotation
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isQuotation(), 404);

        if ($consultationRequest->isPaid()) {
            return redirect()->route('consultation.complete', $consultationRequest);
        }

        if ($consultationRequest->isExpired()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'This quotation has expired. Please contact us for an updated offer.',
            ]);

            return back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        // Reuse or create the CRM contact for the (possibly corrected) email,
        // enriching without wiping existing values with blanks.
        $contact = Contact::resolve([
            'email' => $data['email'],
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        $consultationRequest->update([
            'contact_id' => $contact->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        return redirect()->route('consultation.checkout', $consultationRequest);
    }
}
