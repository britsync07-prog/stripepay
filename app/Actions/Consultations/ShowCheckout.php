<?php

namespace App\Actions\Consultations;

use App\Data\Plan\PlanData;
use App\Data\Quotation\QuotationData;
use App\Models\ConsultationRequest;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayDescriptor;
use App\Payments\PaymentManager;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowCheckout
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        $consultationRequest->loadMissing('plan');

        if ($consultationRequest->isPaid()) {
            return redirect()->route('consultation.complete', $consultationRequest);
        }

        // An unpriced custom lead is not payable until an admin sets a price.
        abort_if($consultationRequest->isCustom() && (int) $consultationRequest->amount <= 0, 404);

        if ($consultationRequest->isExpired()) {
            return Inertia::render('consultation/expired', [
                'offer' => [
                    'title' => $consultationRequest->offerTitle(),
                    'amount' => $consultationRequest->formattedAmount(),
                ],
            ]);
        }

        return Inertia::render('consultation/checkout', [
            'gateways' => $this->gateways($consultationRequest),
            'plan' => $consultationRequest->plan
                ? PlanData::fromModel($consultationRequest->plan)
                : null,
            // A quotation-backed checkout also shows the full scope + line items.
            'quotation' => $consultationRequest->isQuotation()
                ? QuotationData::fromModel($consultationRequest)
                : null,
            // Always-present buyer-facing summary — plan-based or custom.
            'offer' => [
                'title' => $consultationRequest->offerTitle(),
                'description' => $consultationRequest->message,
                'amount' => $consultationRequest->formattedAmount(),
            ],
            'request' => [
                'name' => $consultationRequest->name,
                'email' => $consultationRequest->email,
            ],
        ]);
    }

    private function gateways(ConsultationRequest $consultationRequest): array
    {
        $descriptors = [];

        foreach (app(PaymentManager::class)->configured() as $key => $gateway) {
            $config = $gateway->publicConfig($consultationRequest);

            $descriptors[] = new GatewayDescriptor(
                key: $key,
                label: $gateway->label(),
                title: $gateway->title(),
                mode: $gateway->mode(),
                sessionUrl: route('consultation.pay.session', [$consultationRequest, $key]),
                publishableKey: $config['publishableKey'] ?? null,
                clientId: $config['clientId'] ?? null,
                currency: $config['currency'] ?? null,
                captureUrl: $gateway->mode() === PaymentGateway::MODE_BUTTONS
                    ? route('consultation.pay.capture', [$consultationRequest, $key])
                    : null,
            );
        }

        return $descriptors;
    }
}
