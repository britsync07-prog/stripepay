<?php

namespace App\Actions\Admin;

use App\Models\Invoice;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

class DownloadInvoicePdf
{
    use AsAction;

    public function asController(Request $request, Invoice $invoice)
    {
        $user = $request->user();

        abort_unless($user->hasRole('admin') || $invoice->user_id === $user->id, 403);

        $invoice->load('client', 'consultationRequest', 'items');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => SettingsService::company(),
        ]);

        return $pdf->download("invoice-{$invoice->reference}.pdf");
    }
}
