<?php

namespace App\Actions\Stripe;

use App\Actions\Consultations\ReturnFromGateway;
use App\Actions\Payments\RecordConsultationPayment;
use App\Models\ConsultationRequest;
use App\Payments\PaymentManager;
use App\Services\Payment\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\StripeObject;

/**
 * Signature-verified Stripe webhook — the server-to-server backstop for money-in.
 * Stripe settles a card payment even if the buyer's browser never returns to
 * {@see ReturnFromGateway}; this records that payment
 * through the same idempotent {@see RecordConsultationPayment} (keyed on the
 * session id), so it is safe to run alongside the browser-return path — whichever
 * fires first records, the other no-ops.
 */
class StripeWebhook
{
    use AsAction;

    public function asController(Request $request): Response
    {
        $event = app(StripeCheckout::class)->verifyWebhook(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
        );

        if (! $event) {
            Log::warning('[payment] stripe webhook signature invalid');

            return response('Invalid signature', 400);
        }

        Log::info('[payment] stripe webhook received', ['type' => $event->type]);

        try {
            if ($event->type === 'checkout.session.completed') {
                $this->recordSession($event->data->object);
            }
        } catch (\Throwable $e) {
            Log::error('[payment] stripe webhook processing failed', ['type' => $event->type, 'error' => $e->getMessage()]);
            report($e);

            // Ask Stripe to retry. Recording is idempotent (unique payments.reference),
            // so a retry after a transient failure (Stripe API/DB blip) re-runs the
            // backstop safely; swallowing the error with a 200 would permanently lose
            // a payment the browser-return also missed — defeating the backstop.
            return response('Processing failed', 500);
        }

        return response('', 200);
    }

    private function recordSession(StripeObject $session): void
    {
        $uuid = (string) ($session->metadata['consultation_request_uuid'] ?? '');

        if ($uuid === '') {
            return;
        }

        $consultationRequest = ConsultationRequest::where('uuid', $uuid)->first();

        if (! $consultationRequest || $consultationRequest->isPaid()) {
            return;
        }

        // Reuse the gateway's verify() — it re-retrieves the session by id and
        // re-checks the binding + paid status, so the recorded amount is
        // authoritative and the same guard the browser return relies on applies.
        $verified = app(PaymentManager::class)
            ->gateway('stripe')
            ->verify($consultationRequest, ['session_id' => (string) $session->id]);

        if ($verified) {
            RecordConsultationPayment::run($consultationRequest, 'stripe', $verified);
        }
    }
}
