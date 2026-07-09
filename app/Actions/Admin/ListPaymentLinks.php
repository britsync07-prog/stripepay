<?php

namespace App\Actions\Admin;

use App\Data\ConsultationRequest\PaymentLinkListData;
use App\Data\Quotation\QuotationTemplateData;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\QuotationTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The admin Payment Links surface: standalone priced checkout links (custom
 * requests that are not CRM leads), each with a copyable URL and paid state.
 */
class ListPaymentLinks
{
    use AsAction;

    public function asController(Request $request)
    {
        $links = QueryBuilder::for(ConsultationRequest::query()->paymentLinks())
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(fn ($q) => $q
                        ->where('reference', 'like', "%{$value}%")
                        ->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%"));
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/payment-links', [
            'links' => $links->through(fn (ConsultationRequest $c) => PaymentLinkListData::fromModel($c)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => ConsultationStatus::options(),
            'templates' => QuotationTemplateData::collect(
                QuotationTemplate::query()->active()->latest()->get()
            ),
            'stats' => [
                'total' => ConsultationRequest::query()->paymentLinks()->count(),
                'unpaid' => ConsultationRequest::query()->paymentLinks()
                    ->where('status', ConsultationStatus::Pending->value)->count(),
                'paid' => ConsultationRequest::query()->paymentLinks()
                    ->where('status', ConsultationStatus::Paid->value)->count(),
            ],
        ]);
    }
}
