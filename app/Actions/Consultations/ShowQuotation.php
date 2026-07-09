<?php

namespace App\Actions\Consultations;

use App\Data\Quotation\QuotationData;
use App\Models\ConsultationRequest;
use App\Models\Country;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The public quotation document. The client reviews the offer (scope, line
 * items, validity), confirms their "Prepared for" details, then accepts & pays.
 * Guarded like the checkout: a paid quotation jumps to the completion page; an
 * expired one still renders (read-only) with acceptance disabled.
 */
class ShowQuotation
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isQuotation(), 404);

        if ($consultationRequest->isPaid()) {
            return redirect()->route('consultation.complete', $consultationRequest);
        }

        return Inertia::render('consultation/quotation', [
            'quotation' => QuotationData::fromModel($consultationRequest),
            'acceptUrl' => route('consultation.quote.accept', $consultationRequest),
            'pdfUrl' => route('consultation.quote.pdf', $consultationRequest),
            'countries' => Country::options(),
            // Prefill the "Prepared for" form; the client may correct any of it.
            'contact' => [
                'name' => $consultationRequest->name,
                'company' => $consultationRequest->company,
                'email' => $consultationRequest->email,
                'country' => $consultationRequest->country,
            ],
        ]);
    }
}
