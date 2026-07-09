<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    private function consultation(string $status = 'pending'): ConsultationRequest
    {
        $plan = Plan::create(['name' => 'Call', 'slug' => 'call-'.uniqid(), 'price' => 5000, 'currency' => 'USD']);

        return ConsultationRequest::create([
            'plan_id' => $plan->id,
            'amount' => 5000,
            'currency' => 'USD',
            'status' => $status,
        ]);
    }

    public function test_it_lists_only_configured_gateways_without_initializing_them(): void
    {
        // PayPal configured, Stripe not → exactly one option, and NO API call on load.
        config([
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);

        $consultation = $this->consultation();

        $this->get(route('consultation.checkout', $consultation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/checkout')
                ->has('gateways', 1)
                ->where('gateways.0.key', 'paypal')
                ->where('gateways.0.mode', 'buttons')
                ->where('gateways.0.clientId', 'cid')
                ->whereNot('gateways.0.sessionUrl', null)
                ->whereNot('gateways.0.captureUrl', null),
            );
    }

    public function test_a_paid_consultation_is_redirected_to_the_completion_page(): void
    {
        $consultation = $this->consultation(status: 'paid');

        $this->get(route('consultation.checkout', $consultation))
            ->assertRedirect(route('consultation.complete', $consultation));
    }
}
