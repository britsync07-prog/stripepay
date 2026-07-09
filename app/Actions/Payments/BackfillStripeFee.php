<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use App\Payments\Gateways\StripeGateway;
use App\Services\Payment\StripeCheckout;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class BackfillStripeFee
{
    use AsAction;

    public function handle(Payment $payment, int $attempt = 1): void
    {
        if ($payment->gateway !== 'stripe' || $payment->fee !== null) {
            return;
        }

        try {
            $session = app(StripeCheckout::class)->retrieveSession(
                $payment->reference,
                ['payment_intent.latest_charge.balance_transaction'],
            );

            [$fee, $net] = StripeGateway::feeNet($session);

            if ($fee !== null) {
                $payment->update(['fee' => $fee, 'net' => $net]);
                Log::info('[payment] stripe fee backfilled', [
                    'payment' => $payment->id,
                    'fee' => $fee,
                    'net' => $net,
                ]);

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('[payment] stripe fee backfill failed', [
                'payment' => $payment->id,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);
        }

        if ($attempt < 6) {
            self::dispatch($payment, $attempt + 1)->delay(now()->addSeconds(60));
        } else {
            Log::warning('[payment] stripe fee unavailable after retries', ['payment' => $payment->id]);
        }
    }
}
