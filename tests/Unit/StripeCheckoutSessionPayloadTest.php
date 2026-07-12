<?php

namespace Tests\Unit;

use App\Models\ConsultationRequest;
use App\Services\Payment\StripeCheckout;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeCheckoutSessionPayloadTest extends TestCase
{
    public function test_standalone_payment_links_request_3ds_and_known_client_metadata(): void
    {
        $payload = $this->payloadFor(new ConsultationRequest([
            'uuid' => 'link-uuid',
            'reference' => 'CD-2026-000123',
            'plan_id' => null,
            'title' => 'Strategy call',
            'amount' => 5000,
            'currency' => 'USD',
        ]));

        $this->assertSame('always', $payload['customer_creation']);
        $this->assertSame('any', $payload['payment_method_options']['card']['request_three_d_secure']);
        $this->assertSame('standalone', $payload['metadata']['payment_link_type']);
        $this->assertSame('admin_payment_link', $payload['metadata']['checkout_source']);
        $this->assertSame('known_by_admin', $payload['metadata']['client_relationship']);
        $this->assertSame($payload['metadata'], $payload['payment_intent_data']['metadata']);
        $this->assertSame('Strategy call CD-2026-000123', $payload['payment_intent_data']['description']);
        $this->assertSame('Strategy call', $payload['line_items'][0]['price_data']['product_data']['name']);
    }

    public function test_high_value_consultation_checkouts_request_3ds(): void
    {
        $payload = $this->payloadFor(new ConsultationRequest([
            'uuid' => 'consultation-uuid',
            'reference' => 'CD-2026-000124',
            'plan_id' => 1,
            'title' => 'Consultation',
            'amount' => 5001,
            'currency' => 'USD',
        ]));

        $this->assertSame('any', $payload['payment_method_options']['card']['request_three_d_secure']);
        $this->assertSame('consultation', $payload['metadata']['payment_link_type']);
        $this->assertSame('standard', $payload['metadata']['client_relationship']);
    }

    public function test_low_value_consultation_checkouts_keep_stripe_automatic_3ds(): void
    {
        $payload = $this->payloadFor(new ConsultationRequest([
            'uuid' => 'consultation-uuid',
            'reference' => 'CD-2026-000125',
            'plan_id' => 1,
            'title' => 'Consultation',
            'amount' => 5000,
            'currency' => 'USD',
        ]));

        $this->assertSame('automatic', $payload['payment_method_options']['card']['request_three_d_secure']);
        $this->assertSame('automatic', $payload['metadata']['strong_customer_authentication']);
    }

    private function payloadFor(ConsultationRequest $request): array
    {
        if ($request->plan_id !== null) {
            $request->setRelation('plan', null);
        }

        $checkout = new StripeCheckout(new StripeClient('sk_test'));
        $method = new \ReflectionMethod($checkout, 'sessionPayload');

        return $method->invoke($checkout, $request, 'https://example.com/return');
    }
}
