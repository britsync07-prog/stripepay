<?php

namespace App\Events;

use App\Models\ConsultationRequest;
use App\Models\Expert;
use Illuminate\Foundation\Events\Dispatchable;

/** An expert was assigned to (or cleared from) a consultation. */
class ExpertAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly ConsultationRequest $consultation,
        public readonly ?Expert $expert,
    ) {}
}
