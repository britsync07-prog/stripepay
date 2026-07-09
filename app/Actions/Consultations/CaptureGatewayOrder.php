<?php

namespace App\Actions\Consultations;

use App\Actions\Payments\RecordConsultationPayment;
use App\Models\ConsultationRequest;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Captures an approved SDK/"buttons" order (e.g. PayPal), records the payment
 * via {@see RecordConsultationPayment}, logs a freshly-provisioned client in,
 * and returns the URL to redirect to — called by the buttons' onApprove callback.
 */
class CaptureGatewayOrder
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest, string $gateway): JsonResponse
    {
        $gateways = app(PaymentManager::class)->configured();
        abort_unless(isset($gateways[$gateway]), 404);

        $consultationRequest->loadMissing('plan');

        if ($consultationRequest->isPaid()) {
            return response()->json(['ok' => true, 'redirect' => route('consultation.complete', $consultationRequest)]);
        }

        try {
            $verified = $gateways[$gateway]->verify($consultationRequest, $request->all());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Payment could not be completed.'], 422);
        }

        if (! $verified) {
            return response()->json(['ok' => false, 'message' => 'Payment could not be verified.'], 422);
        }

        $newClient = RecordConsultationPayment::run($consultationRequest, $gateway, $verified);

        if ($newClient && $newClient->wasRecentlyCreated && ! Auth::check()) {
            Auth::login($newClient);
            $request->session()->regenerate();
        }

        return response()->json(['ok' => true, 'redirect' => route('consultation.complete', $consultationRequest)]);
    }
}
