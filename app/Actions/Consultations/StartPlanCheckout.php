<?php

namespace App\Actions\Consultations;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use Lorisleiva\Actions\Concerns\AsAction;

class StartPlanCheckout
{
    use AsAction;

    public function handle(Plan $plan): ConsultationRequest
    {
        return ConsultationRequest::create([
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => ConsultationStatus::Pending,
        ]);
    }

    public function asController(Plan $plan)
    {
        $request = $this->handle($plan);

        return redirect()->route('consultation.checkout', $request);
    }
}
