<?php

namespace App\Actions\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentFlagged;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The single, gateway-agnostic place a recorded payment is flagged after the
 * fact (refunded / disputed / failed): updates status, stamps the payload,
 * writes an activity-log entry, and alerts admins. Used by both gateway webhooks.
 */
class FlagPayment
{
    use AsAction;

    public function handle(Payment $payment, string $reason, ?int $refundedMinor = null, ?string $eventId = null): bool
    {
        // A partial refund (less than the full charge) must not flip the whole
        // payment/invoice to Refunded — that would misstate how much was returned.
        // Record the partial amount instead and leave the paid status intact.
        if ($reason === PaymentStatus::Refunded->value
            && $refundedMinor !== null
            && $refundedMinor < (int) $payment->amount) {
            return $this->recordPartialRefund($payment, $refundedMinor, $eventId);
        }

        if ($payment->status->value === $reason) {
            return false;
        }

        $payment->update([
            'status' => $reason,
            'payload' => array_merge($payment->payload ?? [], [
                $reason.'_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::warning('[payment] flagged', [
            'payment_id' => $payment->id,
            'gateway' => $payment->gateway,
            'reason' => $reason,
            'reference' => $payment->reference,
        ]);

        $consultationRequest = $payment->consultationRequest;

        if (! $consultationRequest) {
            return true;
        }

        if ($reason === PaymentStatus::Refunded->value) {
            $invoice = Invoice::where('consultation_request_id', $consultationRequest->id)->first();

            if ($invoice && $invoice->status !== InvoiceStatus::Refunded) {
                $invoice->update(['status' => InvoiceStatus::Refunded]);

                activity('invoice')
                    ->performedOn($invoice)
                    ->withProperties(['type' => 'refunded', 'message' => "Invoice {$invoice->reference} refunded"])
                    ->log('Invoice refunded');
            }
        }

        activity('payment')
            ->performedOn($consultationRequest)
            ->withProperties([
                'payment_id' => $payment->id,
                'gateway' => $payment->gateway,
                'reference' => $payment->reference,
                'reason' => $reason,
            ])
            ->log('Payment '.$reason);

        $consultationRequest->loadMissing('plan');

        PaymentFlagged::dispatch($consultationRequest, $reason);

        return true;
    }

    /**
     * Record a partial refund: accumulate the refunded total on the payment
     * payload (idempotent on the refund event id) and alert admins, without
     * changing the payment or invoice status — the charge was only partly returned.
     */
    private function recordPartialRefund(Payment $payment, int $refundedMinor, ?string $eventId): bool
    {
        $payload = $payment->payload ?? [];
        $processed = $payload['partial_refund_ids'] ?? [];

        if ($eventId !== null && in_array($eventId, $processed, true)) {
            return false;
        }

        if ($eventId !== null) {
            $processed[] = $eventId;
        }

        $payload['partial_refund_ids'] = $processed;
        $payload['refunded_total'] = (int) ($payload['refunded_total'] ?? 0) + $refundedMinor;
        $payload['last_partial_refund_at'] = now()->toIso8601String();

        $payment->update(['payload' => $payload]);

        Log::warning('[payment] partial refund recorded', [
            'payment_id' => $payment->id,
            'gateway' => $payment->gateway,
            'reference' => $payment->reference,
            'refunded' => $refundedMinor,
            'refunded_total' => $payload['refunded_total'],
        ]);

        $consultationRequest = $payment->consultationRequest;

        if (! $consultationRequest) {
            return true;
        }

        activity('payment')
            ->performedOn($consultationRequest)
            ->withProperties([
                'payment_id' => $payment->id,
                'gateway' => $payment->gateway,
                'reference' => $payment->reference,
                'reason' => 'partially_refunded',
                'refunded' => $refundedMinor,
            ])
            ->log('Payment partially refunded');

        $consultationRequest->loadMissing('plan');

        PaymentFlagged::dispatch($consultationRequest, PaymentStatus::Refunded->value);

        return true;
    }
}
