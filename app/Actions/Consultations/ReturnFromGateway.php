<?php

namespace App\Actions\Consultations;

use App\Actions\Payments\RecordConsultationPayment;
use App\Models\ConsultationRequest;
use App\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The single return landing for every gateway. Verifies the provider's callback
 * (query params), records the payment via {@see RecordConsultationPayment}, logs
 * a freshly-provisioned client in, then shows the completion page.
 */
class ReturnFromGateway
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest, string $gateway)
    {
        $consultationRequest->loadMissing('plan');

        $gateways = app(PaymentManager::class)->configured();

        if (isset($gateways[$gateway]) && ! $consultationRequest->isPaid()) {
            try {
                $verified = $gateways[$gateway]->verify($consultationRequest, $request->query());

                if ($verified) {
                    $newClient = RecordConsultationPayment::run($consultationRequest, $gateway, $verified);

                    if ($newClient && $newClient->wasRecentlyCreated && ! Auth::check()) {
                        Auth::login($newClient);
                        $request->session()->regenerate();
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('consultation.complete', $consultationRequest);
    }
}
