<?php

namespace App\Actions\Paypal;

use App\Actions\Payments\FlagPayment;
use App\Actions\Payments\RecordConsultationPayment;
use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Payments\VerifiedPayment;
use App\Services\Payment\PayPalCheckout;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Signature-verified PayPal webhook. Backs up the on-page capture (recording via
 * the idempotent {@see RecordConsultationPayment}, keyed on the order id) and
 * flags refunds / denials / disputes via {@see FlagPayment} — both never double.
 */
class PaypalWebhook
{
    use AsAction;

    private const TRANSMISSION_HEADERS = [
        'paypal-auth-algo',
        'paypal-cert-url',
        'paypal-transmission-id',
        'paypal-transmission-sig',
        'paypal-transmission-time',
    ];

    public function asController(Request $request): Response
    {
        $event = $request->json()->all();

        $headers = [];
        foreach (self::TRANSMISSION_HEADERS as $header) {
            $headers[$header] = $request->header($header);
        }

        $eventType = $event['event_type'] ?? 'unknown';
        Log::info('[payment] paypal webhook received', ['event_type' => $eventType]);

        if (! PayPalCheckout::make()->verifyWebhook($headers, $event)) {
            Log::warning('[payment] paypal webhook signature invalid', ['event_type' => $eventType]);

            return response('Invalid signature', 400);
        }

        $resource = $event['resource'] ?? [];

        try {
            match ($eventType) {
                'PAYMENT.CAPTURE.COMPLETED' => $this->recordCapture($resource),
                'PAYMENT.CAPTURE.REFUNDED' => $this->flag(
                    $this->captureIdFromUpLink($resource),
                    'refunded',
                    $this->refundedMinor($resource),
                    $resource['id'] ?? null,
                ),
                'PAYMENT.CAPTURE.DENIED' => $this->flag($resource['id'] ?? null, 'failed'),
                'CUSTOMER.DISPUTE.CREATED' => $this->flag($resource['disputed_transactions'][0]['seller_transaction_id'] ?? null, 'disputed'),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('[payment] paypal webhook processing failed', ['event_type' => $eventType, 'error' => $e->getMessage()]);
            report($e);
        }

        return response('', 200);
    }

    /** @param  array<string, mixed>  $resource */
    private function recordCapture(array $resource): void
    {
        $uuid = $resource['custom_id'] ?? null;

        // Match the on-page flow's idempotency key: the order id, not the capture id.
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? ($resource['id'] ?? null);

        if (! $uuid || ! $orderId) {
            return;
        }

        $consultationRequest = ConsultationRequest::where('uuid', $uuid)->first();

        if (! $consultationRequest) {
            return;
        }

        $amount = $resource['amount'] ?? [];
        $currency = strtoupper((string) ($amount['currency_code'] ?? $consultationRequest->currency));
        $amountMinor = isset($amount['value'])
            ? Money::of($amount['value'], $currency)->getMinorAmount()->toInt()
            : 0;

        $breakdown = $resource['seller_receivable_breakdown'] ?? [];
        $feeMinor = isset($breakdown['paypal_fee']['value'])
            ? Money::of($breakdown['paypal_fee']['value'], $currency)->getMinorAmount()->toInt()
            : null;
        $netMinor = isset($breakdown['net_amount']['value'])
            ? Money::of($breakdown['net_amount']['value'], $currency)->getMinorAmount()->toInt()
            : null;

        $verified = new VerifiedPayment(
            reference: (string) $orderId,
            amountMinor: $amountMinor,
            currency: $currency,
            feeMinor: $feeMinor,
            netMinor: $netMinor,
            raw: [
                'transaction_id' => $resource['id'] ?? null,
                'capture_id' => $resource['id'] ?? null,
                'source' => 'webhook',
            ],
        );

        RecordConsultationPayment::run($consultationRequest, 'paypal', $verified);
    }

    /** Flag the payment behind a capture id (refunded / denied / disputed). */
    private function flag(?string $captureId, string $reason, ?int $refundedMinor = null, ?string $eventId = null): void
    {
        if (! $captureId) {
            return;
        }

        $payment = Payment::where('gateway', 'paypal')
            ->where('payload->capture_id', $captureId)
            ->first();

        if ($payment) {
            FlagPayment::run($payment, $reason, $refundedMinor, $eventId);
        }
    }

    /** The refunded amount in minor units, when the refund event carries one. */
    private function refundedMinor(array $resource): ?int
    {
        $amount = $resource['amount'] ?? [];

        if (! isset($amount['value'])) {
            return null;
        }

        $currency = strtoupper((string) ($amount['currency_code'] ?? 'USD'));

        return Money::of($amount['value'], $currency)->getMinorAmount()->toInt();
    }

    /** A refund event references its capture via the `up` link. */
    private function captureIdFromUpLink(array $resource): ?string
    {
        foreach ($resource['links'] ?? [] as $link) {
            if (($link['rel'] ?? null) === 'up' && ! empty($link['href'])) {
                return basename((string) parse_url($link['href'], PHP_URL_PATH));
            }
        }

        return null;
    }
}
