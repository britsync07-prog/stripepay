<?php

namespace App\Actions\Admin;

use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\Contact;
use App\Notifications\LeadCreatedNotification;
use App\Services\AI\Ai;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Adds a prospect to the pipeline by hand (someone an admin spoke to off-platform).
 * A manual lead is an unpriced custom {@see ConsultationRequest} — so once it is
 * priced and paid it flows through the exact same path as an inbound request.
 */
class StoreLead
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate([
            // Client details are optional — an admin may not know them yet. When
            // blank, the client fills them in themselves via the quotation/payment
            // link before paying.
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'message' => ['nullable', 'string', 'max:5000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        // A CRM contact is keyed by email — only resolve one when we have an email.
        // Without it the lead simply has no linked person yet.
        $contact = ! empty($data['email'])
            ? Contact::resolve([
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'company' => $data['company'] ?? null,
                'country' => $data['country'] ?? null,
            ])
            : null;

        $lead = ConsultationRequest::create([
            'plan_id' => null,
            'contact_id' => $contact?->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
            'message' => $data['message'] ?? null,
            'amount' => 0,
            'currency' => SettingsService::currencyCode(),
            'status' => ConsultationStatus::Pending,
            'lead_stage' => LeadStage::New,
            'lead_source' => 'manual',
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
        ]);

        activity('lead')
            ->causedBy($request->user())
            ->performedOn($lead)
            ->withProperties(['type' => 'created', 'message' => 'Lead added manually'])
            ->log('Lead added');

        // A manual lead spins up its delivery project (Planning) too, matching the
        // inbound custom-request path; it flips to Active on payment.
        CreateProjectFromConsultation::run($lead);

        // Touch base with the prospect by email (queued) — a lead is not an
        // account, so route the mail to their address directly. Skipped when we
        // have no email yet (the client will supply it via the link).
        if (! empty($data['email'])) {
            Notification::route('mail', $data['email'])
                ->notify(new LeadCreatedNotification($lead));
        }

        if (Ai::configured()) {
            ScoreLead::dispatch($lead);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lead added.']);

        return back();
    }
}
