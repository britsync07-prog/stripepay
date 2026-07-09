<?php

namespace App\Actions\Admin;

use App\Data\Invoice\InvoiceListData;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListInvoices
{
    use AsAction;

    public function asController(Request $request)
    {
        $invoices = QueryBuilder::for(Invoice::class)
            ->with('client')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/invoices', [
            'invoices' => $invoices->through(fn (Invoice $i) => InvoiceListData::fromModel($i)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => InvoiceStatus::options(),
            'stats' => [
                'total' => Invoice::count(),
                'paid' => Invoice::where('status', InvoiceStatus::Paid->value)->count(),
                'refunded' => Invoice::where('status', InvoiceStatus::Refunded->value)->count(),
            ],
        ]);
    }
}
