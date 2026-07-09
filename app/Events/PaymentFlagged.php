<?php

namespace App\Events;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Events\Dispatchable;

/** A recorded payment was flagged after the fact (refunded / disputed / failed). */
class PaymentFlagged
{
    use Dispatchable;

    public function __construct(
        public readonly ConsultationRequest $consultation,
        public readonly string $reason,
    ) {}
}
