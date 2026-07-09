<?php

namespace App\Actions\Admin;

use App\Actions\Consultations\ShowQuotation;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\QuotationTemplate;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Creates a standalone, client-less quotation link — optionally pre-filled from a
 * template. No name/email is needed: the buyer fills their own "Prepared for"
 * details on the public quotation page ({@see ShowQuotation})
 * and pays through the normal checkout. Not a CRM lead (lead_stage stays null),
 * so it lives under Payment Links. The admin lands on the builder to review/tweak.
 */
class CreateQuotationLink
{
    use AsAction;

    public function asController(Request $request)
    {
        $data = $request->validate([
            'template' => ['nullable', 'string', 'exists:quotation_templates,uuid'],
        ]);

        $link = ConsultationRequest::create([
            'plan_id' => null,
            'title' => 'Quotation',
            'amount' => 0,
            'currency' => SettingsService::currencyCode(),
            'status' => ConsultationStatus::Pending,
        ]);

        if (! empty($data['template'])) {
            $template = QuotationTemplate::where('uuid', $data['template'])->firstOrFail();

            // Template line items are already in minor units — issue directly.
            BuildQuotation::issue(
                consultationRequest: $link,
                minorItems: $template->line_items ?? [],
                scopeItems: $template->scope_items ?? [],
                title: $template->title,
                deliveryTimeline: $template->delivery_timeline,
                validDays: $template->valid_days,
                causer: $request->user(),
            );
        }

        activity('payment')
            ->causedBy($request->user())
            ->performedOn($link)
            ->withProperties([
                'type' => 'payment_link',
                'message' => 'Created quotation link',
            ])
            ->log('Created quotation link');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quotation link created — review it, then copy or email the link.',
        ]);

        return redirect()->route('admin.consultation.payment-link', $link);
    }
}
