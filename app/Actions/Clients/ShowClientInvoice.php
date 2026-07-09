<?php

namespace App\Actions\Clients;

use App\Data\Invoice\InvoiceData;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientInvoice
{
    use AsAction;

    public function asController(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 404);

        $invoice->load('client', 'consultationRequest', 'items');

        return Inertia::render('portal/invoice', [
            'invoice' => InvoiceData::fromModel($invoice),
        ]);
    }
}
