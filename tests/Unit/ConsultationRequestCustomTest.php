<?php

namespace Tests\Unit;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use Tests\TestCase;

/**
 * Pure-logic coverage for the custom-offer helpers on ConsultationRequest.
 * Uses unsaved models — no DB, no container.
 */
class ConsultationRequestCustomTest extends TestCase
{
    public function test_is_custom_is_true_when_no_plan(): void
    {
        $custom = new ConsultationRequest(['plan_id' => null]);
        $planned = new ConsultationRequest(['plan_id' => 7]);

        $this->assertTrue($custom->isCustom());
        $this->assertFalse($planned->isCustom());
    }

    public function test_offer_title_prefers_custom_title_then_plan_then_default(): void
    {
        $withTitle = new ConsultationRequest(['title' => 'Bespoke Advisory']);
        $this->assertSame('Bespoke Advisory', $withTitle->offerTitle());

        $withPlan = new ConsultationRequest;
        $withPlan->setRelation('plan', new Plan(['name' => 'Strategy Call']));
        $this->assertSame('Strategy Call', $withPlan->offerTitle());

        $bare = new ConsultationRequest;
        $this->assertSame('Consultation', $bare->offerTitle());
    }

    public function test_is_expired_only_when_past_and_unpaid(): void
    {
        $expired = new ConsultationRequest([
            'status' => ConsultationStatus::Pending,
            'expires_at' => now()->subDay(),
        ]);
        $this->assertTrue($expired->isExpired());

        $future = new ConsultationRequest([
            'status' => ConsultationStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);
        $this->assertFalse($future->isExpired());

        // A paid request is never "expired" even if the pay-by date has passed.
        $paidPastDate = new ConsultationRequest([
            'status' => ConsultationStatus::Paid,
            'expires_at' => now()->subDay(),
        ]);
        $this->assertFalse($paidPastDate->isExpired());

        // No pay-by date (plan checkouts) → never expires.
        $noDate = new ConsultationRequest(['status' => ConsultationStatus::Pending]);
        $this->assertFalse($noDate->isExpired());
    }
}
