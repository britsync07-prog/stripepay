<?php

namespace App\Actions\Admin;

use App\Data\ConsultationRequest\ConsultationData;
use App\Data\ConsultationRequest\PaymentLinkData;
use App\Data\Quotation\QuotationTemplateData;
use App\Models\ConsultationRequest;
use App\Models\QuotationTemplate;
use Brick\Money\Money;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * A dedicated page for a custom request's checkout link: the admin sets/edits
 * the price here and gets the standalone, shareable payment link. Reached after
 * pricing (SetConsultationPrice redirects here) or from the consultation sidebar.
 */
class ShowPaymentLink
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        // Pricing + a manual payment link only exist for custom requests.
        abort_unless($consultationRequest->isCustom(), 404);

        $currency = (string) $consultationRequest->currency;

        // Prefill the (editable) price form with the current amount in major units.
        $amountMajor = (int) $consultationRequest->amount > 0
            ? (string) Money::ofMinor((int) $consultationRequest->amount, $currency)->getAmount()
            : '';

        $expiresInDays = $consultationRequest->expires_at
            ? max(1, (int) ceil(now()->floatDiffInDays($consultationRequest->expires_at, false)))
            : 14;

        // Stored line items hold minor units; the builder edits in major units.
        $lineItems = array_map(fn (array $item) => [
            'description' => $item['description'],
            'quantity' => (int) $item['quantity'],
            'unit_amount' => (string) Money::ofMinor((int) $item['unit_amount'], $currency)->getAmount(),
        ], $consultationRequest->line_items ?? []);

        return Inertia::render('admin/consultation-payment-link', [
            'consultation' => ConsultationData::fromModel($consultationRequest),
            'paymentLink' => PaymentLinkData::fromModel($consultationRequest),
            'priceInitial' => [
                'title' => $consultationRequest->title ?? '',
                'amount' => $amountMajor,
                'expires_in_days' => $expiresInDays,
            ],
            'quotationInitial' => [
                'title' => $consultationRequest->title ?? '',
                'scope_items' => $consultationRequest->scope_items ?? [],
                'line_items' => $lineItems,
                'expires_in_days' => $expiresInDays,
                'delivery_timeline' => $consultationRequest->delivery_timeline ?? '',
                'issued' => $consultationRequest->isQuotation(),
            ],
            'templates' => QuotationTemplateData::collect(
                QuotationTemplate::query()->active()->latest()->get()
            ),
        ]);
    }
}
