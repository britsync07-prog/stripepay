<?php

namespace App\Events;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Events\Dispatchable;

/** A consultation's payment was recorded and it is now paid. */
class ConsultationPaid
{
    use Dispatchable;

    public function __construct(public readonly ConsultationRequest $consultation) {}
}
