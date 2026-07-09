<?php

namespace App\Payments\Gateways;

use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\CheckoutUrls;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\VerifiedPayment;
use App\Services\Payment\PayPalCheckout;
use Brick\Money\Money;
use Illuminate\Support\Facades\Log;

/** PayPal Orders v2 adapter — on-page Smart Buttons. Wraps {@see PayPalCheckout}. */
class PaypalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function title(): string
    {
        return 'Pay with PayPal';
    }

    public function mode(): string
    {
        return self::MODE_BUTTONS;
    }

    public function isConfigured(): bool
    {
        return PayPalCheckout::configured();
    }

    public function publicConfig(ConsultationRequest $request): array
    {
        return [
            'clientId' => (string) config('services.paypal.client_id'),
            'currency' => strtoupper((string) $request->currency),
        ];
    }

    public function begin(ConsultationRequest $request, CheckoutUrls $urls): CheckoutSession
    {
        $order = PayPalCheckout::make()->createOrder($request, $urls->returnUrl, $urls->cancelUrl);

        $request->update([
            'checkout_gateway' => $this->key(),
            'checkout_reference' => $order['id'] ?? null,
        ]);

        return new CheckoutSession(
            mode: $this->mode(),
            reference: $order['id'] ?? null,
        );
    }

    public function verify(ConsultationRequest $request, array $callback): ?VerifiedPayment
    {
        // 'orderID' from the JS SDK onApprove; 'token' from a redirect return.
        $orderId = (string) ($callback['orderID'] ?? $callback['token'] ?? '');

        if ($orderId === '') {
            return null;
        }

        $checkout = PayPalCheckout::make();

        // Validate the APPROVED order — binding + amount/currency — BEFORE capturing.
        // We only take the buyer's money once we know we can credit it correctly.
        if (! $this->orderMatchesConsultation($checkout->getOrder($orderId), $request)) {
            Log::warning('[payment] paypal order rejected before capture (binding/amount mismatch)', [
                'consultation' => $request->uuid,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $capture = $checkout->captureOrder($orderId);

        if (($capture['status'] ?? null) !== 'COMPLETED') {
            return null;
        }

        $unit = $capture['purchase_units'][0] ?? [];
        $node = $unit['payments']['captures'][0] ?? [];
        $charged = $node['amount'] ?? [];
        $currency = strtoupper((string) ($charged['currency_code'] ?? $request->currency));
        $amountMinor = isset($charged['value'])
            ? Money::of($charged['value'], $currency)->getMinorAmount()->toInt()
            : 0;

        // PayPal reports its fee and the net payable directly on the capture.
        $breakdown = $node['seller_receivable_breakdown'] ?? [];
        $feeMinor = isset($breakdown['paypal_fee']['value'])
            ? Money::of($breakdown['paypal_fee']['value'], $currency)->getMinorAmount()->toInt()
            : null;
        $netMinor = isset($breakdown['net_amount']['value'])
            ? Money::of($breakdown['net_amount']['value'], $currency)->getMinorAmount()->toInt()
            : null;

        $payer = $capture['payer'] ?? [];
        $payerName = trim(($payer['name']['given_name'] ?? '').' '.($payer['name']['surname'] ?? ''));

        return new VerifiedPayment(
            reference: $orderId,
            amountMinor: $amountMinor,
            currency: $currency,
            payerName: $payerName ?: null,
            payerEmail: $payer['email_address'] ?? null,
            payerPhone: $payer['phone']['phone_number']['national_number'] ?? null,
            payerCountry: $payer['address']['country_code'] ?? null,
            feeMinor: $feeMinor,
            netMinor: $netMinor,
            raw: [
                'transaction_id' => $node['id'] ?? null,
                'capture_id' => $node['id'] ?? null,
                'payer_email' => $payer['email_address'] ?? null,
                'status' => $capture['status'] ?? null,
            ],
        );
    }

    public function afterRecorded(Payment $payment): void {}

    /**
     * Is this approved order safe to capture for this consultation? Checks the
     * binding (`custom_id` = uuid) and that the order's amount/currency exactly
     * match the consultation — so we never capture money we can't credit.
     *
     * @param  array<string, mixed>  $order
     */
    private function orderMatchesConsultation(array $order, ConsultationRequest $request): bool
    {
        $unit = $order['purchase_units'][0] ?? [];

        // Binding must be present AND match — fail closed, never credit the wrong
        // consultation just because PayPal omitted the custom_id.
        if (($unit['custom_id'] ?? null) !== $request->uuid) {
            return false;
        }

        $amount = $unit['amount'] ?? [];
        if (! isset($amount['value'], $amount['currency_code'])) {
            return false;
        }

        $currency = strtoupper((string) $amount['currency_code']);
        $valueMinor = Money::of($amount['value'], $currency)->getMinorAmount()->toInt();

        return $currency === strtoupper((string) $request->currency)
            && $valueMinor === (int) $request->amount;
    }
}
