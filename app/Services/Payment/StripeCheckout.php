<?php

namespace App\Services\Payment;

use App\Models\ConsultationRequest;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckout
{
    public function __construct(private readonly StripeClient $stripe) {}

    public static function make(): self
    {
        return new self(new StripeClient((string) config('services.stripe.secret')));
    }

    public function createSession(ConsultationRequest $request, string $returnUrl): Session
    {
        return $this->stripe->checkout->sessions->create(
            $this->sessionPayload($request, $returnUrl),
        );
    }

    private function sessionPayload(ConsultationRequest $request, string $returnUrl): array
    {
        $metadata = [
            'consultation_request_uuid' => $request->uuid,
            'consultation_reference' => (string) $request->reference,
            'payment_link_type' => $request->isPaymentLink() ? 'standalone' : 'consultation',
            'checkout_source' => $request->isPaymentLink() ? 'admin_payment_link' : 'consultation_checkout',
            'client_relationship' => $request->isPaymentLink() ? 'known_by_admin' : 'standard',
            'strong_customer_authentication' => 'automatic',
        ];

        return [
            'ui_mode' => 'embedded_page',
            'mode' => 'payment',
            'return_url' => $returnUrl,

            // Keep the checkout in the platform's operating currency only; disable
            // Stripe Adaptive Pricing, which otherwise offers the buyer a local-currency
            // conversion (e.g. MAD for a Morocco IP) alongside the USD amount.
            'adaptive_pricing' => ['enabled' => false],

            'billing_address_collection' => 'required',
            'phone_number_collection' => ['enabled' => true],
            'customer_creation' => 'always',
            ...($request->email ? ['customer_email' => $request->email] : []),
            'payment_method_options' => [
                'card' => [
                    'request_three_d_secure' => 'automatic',
                ],
            ],
            'payment_intent_data' => [
                'description' => $request->offerTitle().' '.$request->reference,
                'metadata' => $metadata,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($request->currency),
                    'unit_amount' => $request->amount,
                    'product_data' => [
                        'name' => $request->offerTitle(),

                        ...($request->plan?->description ? ['description' => $request->plan->description] : []),
                    ],
                ],
            ]],
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array<int, string>  $expand  Relations to expand (e.g. the charge's
     *                                      balance transaction, to read the fee/net).
     */
    public function retrieveSession(string $sessionId, array $expand = []): Session
    {
        return $this->stripe->checkout->sessions->retrieve(
            $sessionId,
            $expand ? ['expand' => $expand] : [],
        );
    }

    /**
     * Verify a webhook payload against the signing secret. Returns the parsed
     * event, or null when the secret/signature is missing or invalid (fail closed).
     */
    public function verifyWebhook(string $payload, string $signature): ?Event
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '' || $signature === '') {
            return null;
        }

        try {
            return Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return null;
        }
    }
}
