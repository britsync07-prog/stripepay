<?php

namespace App\Actions\Consultations;

use App\Data\ConsultationRequest\ConsultationRequestData;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use App\Services\SettingsService;
use Lorisleiva\Actions\Concerns\AsAction;

class StoreConsultationRequest
{
    use AsAction;

    public function handle(Plan $plan, ConsultationRequestData $data): ConsultationRequest
    {
        return ConsultationRequest::create([
            'plan_id' => $plan->id,
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'company' => $data->company,
            'country' => $data->country,
            'message' => $data->message,
            'amount' => $plan->price,
            'currency' => SettingsService::currencyCode(),
            'status' => ConsultationStatus::Pending,
        ]);
    }

    public function asController(Plan $plan, ConsultationRequestData $data)
    {
        $request = $this->handle($plan, $data);

        return redirect()->route('consultation.checkout', $request);
    }
}
