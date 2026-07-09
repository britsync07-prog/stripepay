<?php

namespace App\Events;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Events\Dispatchable;

/** A prospect submitted a tailored (custom) plan request — a new inbound lead. */
class CustomPlanRequested
{
    use Dispatchable;

    public function __construct(public readonly ConsultationRequest $consultation) {}
}
