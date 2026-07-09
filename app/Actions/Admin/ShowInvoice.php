<?php

namespace App\Actions\Admin;

use App\Data\Invoice\InvoiceData;
use App\Models\Invoice;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowInvoice
{
    use AsAction;

    public function asController(Invoice $invoice)
    {
        $invoice->load('client', 'consultationRequest', 'items');

        return Inertia::render('admin/invoices/show', [
            'invoice' => InvoiceData::fromModel($invoice),
        ]);
    }
}
