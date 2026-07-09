<?php

namespace App\Data\ConsultationRequest;

use App\Models\ConsultationRequest;
use Brick\Money\Money;
use Spatie\LaravelData\Data;

/**
 * The admin-facing payment-link view model for a consultation: the checkout URL
 * plus the state that drives the sidebar (expiry, custom vs plan, still-unpriced)
 * and the current price (prefills the in-place "manage" edit dialog).
 */
class PaymentLinkData extends Data
{
    public function __construct(
        public string $url,
        public ?string $expiresAt,
        public bool $expired,
        public bool $isCustom,
        public bool $unpriced,
        public ?string $title,
        public string $amount,
        public int $expiresInDays,
        public bool $isQuotation = false,
        public ?string $quoteNumber = null,
    ) {}

    public static function fromModel(ConsultationRequest $request): self
    {
        $currency = (string) $request->currency;

        return new self(
            // The link we share is the quotation document when one was issued,
            // otherwise the bare checkout page.
            url: $request->offerUrl(),
            expiresAt: $request->expires_at?->toFormattedDayDateString(),
            expired: $request->isExpired(),
            isCustom: $request->isCustom(),
            unpriced: $request->isUnpriced(),
            title: $request->title,
            amount: (int) $request->amount > 0
                ? (string) Money::ofMinor((int) $request->amount, $currency)->getAmount()
                : '',
            expiresInDays: $request->expires_at
                ? max(1, (int) ceil(now()->floatDiffInDays($request->expires_at, false)))
                : 14,
            isQuotation: $request->isQuotation(),
            quoteNumber: $request->quote_number,
        );
    }
}
