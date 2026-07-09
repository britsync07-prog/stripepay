<?php

namespace App\Data\Quotation;

use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Services\SettingsService;
use Spatie\LaravelData\Data;

/**
 * The full, buyer-facing quotation document view model — shared by the public
 * quotation page, the checkout order summary and the PDF. Every money value is
 * pre-formatted here so no downstream surface reformats minor units.
 */
class QuotationData extends Data
{
    public function __construct(
        public string $number,
        public ?string $issuedAt,
        public ?string $validUntil,
        public ?int $validityDays,
        public string $title,
        /** @var array<int, string> */
        public array $scopeItems,
        /** @var array<int, QuotationLineItemData> */
        public array $lineItems,
        public string $subtotal,
        public string $taxLabel,
        public string $total,
        public ?string $deliveryTimeline,
        public QuotationPartyData $preparedBy,
        public QuotationPartyData $preparedFor,
        public bool $expired,
        public bool $paid,
    ) {}

    public static function fromModel(ConsultationRequest $request): self
    {
        $currency = (string) $request->currency;
        $company = SettingsService::company();

        $lineItems = array_map(
            fn (array $item) => QuotationLineItemData::fromArray($item, $currency),
            $request->line_items ?? [],
        );

        return new self(
            number: (string) $request->quote_number,
            issuedAt: $request->quote_issued_at?->toFormattedDayDateString(),
            validUntil: $request->expires_at?->toFormattedDayDateString(),
            validityDays: $request->validityDays(),
            title: $request->offerTitle(),
            scopeItems: array_values($request->scope_items ?? []),
            lineItems: $lineItems,
            // Subtotal and total are equal — tax is not applied to quotations.
            subtotal: $request->formattedAmount(),
            taxLabel: 'N/A',
            total: $request->formattedAmount(),
            deliveryTimeline: $request->delivery_timeline,
            preparedBy: new QuotationPartyData(
                name: $company['name'],
                company: null,
                email: $company['email'],
                country: $company['address'],
                signatoryName: $company['signatory_name'] ?? null,
                signatoryTitle: $company['signatory_title'] ?? null,
            ),
            preparedFor: new QuotationPartyData(
                name: $request->name,
                company: $request->company,
                email: $request->email,
                country: Country::nameFor($request->country),
            ),
            expired: $request->isExpired(),
            paid: $request->isPaid(),
        );
    }
}
