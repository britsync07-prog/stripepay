<?php

namespace App\Actions\Clients;

use App\Data\Invoice\InvoiceListData;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientInvoices
{
    use AsAction;

    public function asController(Request $request)
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->with('client')
            ->latest()
            ->get()
            ->map(fn (Invoice $i) => InvoiceListData::fromModel($i));

        return Inertia::render('portal/invoices', [
            'invoices' => $invoices,
        ]);
    }
}
