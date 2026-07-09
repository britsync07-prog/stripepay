<?php

namespace App\Events;

use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use Illuminate\Foundation\Events\Dispatchable;

/** A consultation's work status transitioned (New → InProgress → Resolved → Closed). */
class ConsultationStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly ConsultationRequest $consultation,
        public readonly ConsultationWorkStatus $to,
        public readonly ?ConsultationWorkStatus $from = null,
    ) {}
}
