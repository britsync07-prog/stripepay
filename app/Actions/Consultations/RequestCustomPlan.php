<?php

namespace App\Actions\Consultations;

use App\Actions\Admin\CreateProjectFromConsultation;
use App\Actions\Admin\ScoreLead;
use App\Data\ConsultationRequest\RequestCustomPlanData;
use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Events\CustomPlanRequested;
use App\Models\ConsultationRequest;
use App\Models\Contact;
use App\Notifications\CustomPlanReceivedNotification;
use App\Services\AI\Ai;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * A prospect/client asks for a tailored plan. Creates an unpriced custom lead
 * (a {@see ConsultationRequest} with no plan and a zero amount) and alerts
 * admins, who set a price and send back the payment link.
 *
 * No account is created or linked here. The submitted email is unverified, so —
 * exactly like the priced guest path — the client is provisioned and the
 * consultation linked only on payment (App\Actions\Clients\ProvisionClientFromConsultation),
 * which is the point ownership of the email is proven. Binding a lead to an
 * existing account on an unverified, client-supplied email would let anyone
 * attach a request to a stranger's portal.
 */
class RequestCustomPlan
{
    use AsAction;

    public function handle(RequestCustomPlanData $data): ConsultationRequest
    {
        $message = $data->budget
            ? 'Budget: '.$data->budget."\n\n".$data->message
            : $data->message;

        // Key the request to a reusable CRM contact (created or reused by email).
        $contact = Contact::resolve([
            'email' => $data->email,
            'name' => $data->name,
            'company' => $data->company,
            'country' => $data->country,
        ]);

        $request = ConsultationRequest::create([
            'plan_id' => null,
            'contact_id' => $contact->id,
            'title' => 'Custom plan request',
            'name' => $data->name,
            'email' => $data->email,
            'company' => $data->company,
            'country' => $data->country,
            'message' => $message,
            'amount' => 0,
            'currency' => SettingsService::currencyCode(),
            'status' => ConsultationStatus::Pending,
            // Inbound requests enter the CRM pipeline as a new lead.
            'lead_stage' => LeadStage::New,
            'lead_source' => 'form',
        ]);

        activity('consultation')
            ->performedOn($request)
            ->withProperties(['email' => $request->email])
            ->log('Client requested a custom plan');

        // Delivery workspace is spun up immediately (status Planning) so the team
        // can scope before payment; RecordConsultationPayment flips it to Active.
        CreateProjectFromConsultation::run($request);

        CustomPlanRequested::dispatch($request);

        // Acknowledge the client by email — no account exists yet, so route the
        // mail to their submitted address directly.
        Notification::route('mail', $request->email)
            ->notify(new CustomPlanReceivedNotification($request));

        if (Ai::configured()) {
            ScoreLead::dispatch($request);
        }

        return $request;
    }

    public function asController(RequestCustomPlanData $data)
    {
        $this->handle($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Thanks — we’ve received your request and will be in touch with a tailored plan.',
        ]);

        return redirect('/');
    }
}
