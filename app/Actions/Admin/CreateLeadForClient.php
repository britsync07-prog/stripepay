<?php

namespace App\Actions\Admin;

use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\Contact;
use App\Models\User;
use App\Services\AI\Ai;
use App\Services\SettingsService;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * One-click "start a new lead" for an existing client. Spins up a fresh custom
 * lead (+ its Planning project) prefilled from the client's known details and
 * already linked to their account and CRM contact — so an admin never re-enters
 * a returning client's data. Lands on the new lead's page, ready to price/work.
 */
class CreateLeadForClient
{
    use AsAction;

    public function asController(User $client)
    {
        abort_unless($client->hasRole('client'), 404);

        // The client's contact holds the richer details (company/country/phone);
        // fall back to their most recent consultation, then link the contact to
        // the (already-verified) client.
        $contact = Contact::resolve(['email' => $client->email, 'name' => $client->name]);
        $latest = $client->consultationRequests()->latest('id')->first();

        if ($contact->user_id !== $client->id) {
            $contact->update(['user_id' => $client->id]);
        }

        $lead = ConsultationRequest::create([
            'plan_id' => null,
            'user_id' => $client->id,
            'contact_id' => $contact->id,
            'name' => $client->name,
            'email' => $client->email,
            'company' => $contact->company ?: $latest?->company,
            'country' => $contact->country ?: $latest?->country,
            'phone' => $contact->phone ?: $latest?->phone,
            'amount' => 0,
            'currency' => SettingsService::currencyCode(),
            'status' => ConsultationStatus::Pending,
            'lead_stage' => LeadStage::New,
            'lead_source' => 'manual',
        ]);

        activity('lead')
            ->causedBy(request()->user())
            ->performedOn($lead)
            ->withProperties(['type' => 'created', 'message' => 'Lead created for existing client'])
            ->log('Lead created for client');

        // Same delivery-workspace + scoring path as any other manual lead.
        CreateProjectFromConsultation::run($lead);

        if (Ai::configured()) {
            ScoreLead::dispatch($lead);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'New lead started for '.$client->name.'. Price it or add notes below.',
        ]);

        return redirect()->route('admin.consultation', $lead);
    }
}
