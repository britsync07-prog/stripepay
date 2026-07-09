<?php

namespace App\Data\Quotation;

use Spatie\LaravelData\Data;

/**
 * A party on a quotation — the "Prepared by" (agency + signatory) or the
 * "Prepared for" (client). Company is optional on both sides.
 */
class QuotationPartyData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $company = null,
        public ?string $email = null,
        public ?string $country = null,
        public ?string $signatoryName = null,
        public ?string $signatoryTitle = null,
    ) {}
}
