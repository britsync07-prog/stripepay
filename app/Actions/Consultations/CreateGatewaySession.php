<?php

namespace App\Actions\Consultations;

use App\Models\ConsultationRequest;
use App\Payments\CheckoutUrls;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Lazily begins a checkout for the chosen rail and returns what the frontend
 * needs: an embedded gateway's clientSecret, or a buttons gateway's order id.
 * Called only when the buyer actually selects a payment method — nothing is
 * initialized on page load.
 */
class CreateGatewaySession
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest, string $gateway): JsonResponse
    {
        $gateways = app(PaymentManager::class)->configured();
        abort_unless(isset($gateways[$gateway]), 404);
        abort_if($consultationRequest->isPaid(), 409, 'This consultation is already paid.');
        abort_if($consultationRequest->isExpired(), 410, 'This payment link has expired.');
        // An unpriced custom lead is not payable until an admin sets a price.
        abort_if($consultationRequest->isCustom() && (int) $consultationRequest->amount <= 0, 404);

        $consultationRequest->loadMissing('plan');

        $urls = new CheckoutUrls(
            route('consultation.pay.return', [$consultationRequest, $gateway]),
            route('consultation.checkout', $consultationRequest),
        );

        try {
            $session = $gateways[$gateway]->begin($consultationRequest, $urls);
        } catch (\Throwable $e) {
            Log::error('[payment] checkout session failed', [
                'gateway' => $gateway,
                'consultation' => $consultationRequest->uuid,
                'error' => $e->getMessage(),
            ]);
            report($e);

            return response()->json(['message' => 'Could not start checkout. Please try again.'], 422);
        }

        Log::info('[payment] checkout session started', [
            'gateway' => $gateway,
            'consultation' => $consultationRequest->uuid,
            'mode' => $session->mode,
            'reference' => $session->reference,
        ]);

        return response()->json([
            'mode' => $session->mode,
            'clientSecret' => $session->clientSecret,
            'id' => $session->reference,
        ]);
    }
}
