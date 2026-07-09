<?php

namespace App\Actions\Payments;

use App\Actions\Admin\CreateInvoiceFromConsultation;
use App\Actions\Admin\CreateProjectFromConsultation;
use App\Actions\Clients\ProvisionClientFromConsultation;
use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Events\ConsultationPaid;
use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Payments\VerifiedPayment;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The single, gateway-agnostic place a confirmed payment is recorded: validates
 * the charged amount, writes the {@see Payment} idempotently (unique reference),
 * marks the consultation paid, notifies admins, and provisions the client.
 */
class RecordConsultationPayment
{
    use AsAction;

    public function handle(ConsultationRequest $consultationRequest, string $gateway, VerifiedPayment $payment): ?User
    {
        $context = [
            'gateway' => $gateway,
            'consultation' => $consultationRequest->uuid,
            'reference' => $payment->reference,
            'amount' => $payment->amountMinor,
            'currency' => strtoupper($payment->currency),
        ];

        if ($this->mismatches($consultationRequest, $payment)) {
            activity('payment')
                ->performedOn($consultationRequest)
                ->withProperties([
                    'expected' => (int) $consultationRequest->amount,
                    'charged' => $payment->amountMinor,
                    'expected_currency' => strtoupper((string) $consultationRequest->currency),
                    'charged_currency' => strtoupper($payment->currency),
                    'gateway' => $gateway,
                    'reference' => $payment->reference,
                ])
                ->log('Payment amount/currency mismatch — not marked paid');

            return null;
        }

        if (Payment::where('reference', $payment->reference)->exists()) {
            return null;
        }

        $recorded = false;

        DB::transaction(function () use ($consultationRequest, $gateway, $payment, &$recorded) {
            if (Payment::where('reference', $payment->reference)->lockForUpdate()->exists()) {
                return;
            }

            $consultationRequest->update([
                'status' => ConsultationStatus::Paid,
                'paid_at' => now(),
                'name' => $consultationRequest->name ?: ($payment->payerName ?: null),
                'email' => $consultationRequest->email ?: ($payment->payerEmail ?: null),
                'phone' => $consultationRequest->phone ?: ($payment->payerPhone ?: null),
                'country' => $consultationRequest->country ?: ($payment->payerCountry ?: null),
            ]);

            $record = Payment::create([
                'consultation_request_id' => $consultationRequest->id,
                'gateway' => $gateway,
                'reference' => $payment->reference,
                'amount' => $consultationRequest->amount,
                'fee' => $payment->feeMinor,
                'net' => $payment->netMinor,
                'currency' => $consultationRequest->currency,
                'status' => 'paid',
                'paid_at' => now(),
                'payload' => $payment->raw,
            ]);

            activity('payment')
                ->performedOn($consultationRequest)
                ->withProperties([
                    'payment_id' => $record->id,
                    'gateway' => $gateway,
                    'amount' => (int) $consultationRequest->amount,
                    'currency' => $consultationRequest->currency,
                    'reference' => $payment->reference,
                ])
                ->log('Consultation payment completed');

            $recorded = true;
        });

        if (! $recorded) {
            return null;
        }

        ConsultationPaid::dispatch($consultationRequest->refresh());

        $consultationRequest->refresh();

        $linked = $consultationRequest->user;
        $provisioned = null;

        if (! $linked || ! $linked->hasVerifiedEmail()) {
            $provisioned = ProvisionClientFromConsultation::run($consultationRequest);
        }

        $consultationRequest->refresh();

        // A lead that pays is won — move it to the end of the pipeline and drop
        // any pending follow-up reminder.
        if ($consultationRequest->lead_stage && $consultationRequest->lead_stage !== LeadStage::Won) {
            $consultationRequest->update(['lead_stage' => LeadStage::Won, 'next_follow_up_at' => null]);

            activity('lead')
                ->performedOn($consultationRequest)
                ->withProperties(['type' => 'stage', 'to_label' => LeadStage::Won->label(), 'message' => 'Lead won on payment'])
                ->log('Lead won');
        }

        CreateProjectFromConsultation::run($consultationRequest);
        CreateInvoiceFromConsultation::run($consultationRequest);

        $record = Payment::where('reference', $payment->reference)->first();

        if ($record) {
            app(PaymentManager::class)->gateway($gateway)->afterRecorded($record);
        }

        return $provisioned;
    }

    private function mismatches(ConsultationRequest $consultationRequest, VerifiedPayment $payment): bool
    {
        return $payment->amountMinor !== (int) $consultationRequest->amount
            || strtoupper($payment->currency) !== strtoupper((string) $consultationRequest->currency);
    }
}
