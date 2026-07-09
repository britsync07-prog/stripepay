<?php

namespace App\Actions\Consultations;

use App\Data\Quotation\QuotationData;
use App\Models\ConsultationRequest;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Renders the quotation as a PDF. Bound to the request's UUID (the same
 * unguessable secret that gates the public quotation link), so the client can
 * download their own copy without an account.
 */
class DownloadQuotationPdf
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isQuotation(), 404);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => QuotationData::fromModel($consultationRequest),
            'company' => SettingsService::company(),
        ]);

        return $pdf->download("quotation-{$consultationRequest->quote_number}.pdf");
    }
}
