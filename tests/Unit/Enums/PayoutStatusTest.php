<?php

namespace Tests\Unit\Enums;

use App\Enums\PayoutStatus;
use Tests\TestCase;

/**
 * fromProvider() is the single mapping from a rail's status string to ours
 * (e.g. a Stripe transfer settling synchronously → Paid). It must be total and
 * predictable, so it gets a pure unit test.
 */
class PayoutStatusTest extends TestCase
{
    public function test_it_maps_provider_statuses_to_our_status(): void
    {
        $cases = [
            // Settled.
            'processed' => PayoutStatus::Paid,
            'complete' => PayoutStatus::Paid,
            'completed' => PayoutStatus::Paid,
            'paid' => PayoutStatus::Paid,
            // Failed (note both spellings of cancel).
            'failed' => PayoutStatus::Failed,
            'returned' => PayoutStatus::Failed,
            'cancelled' => PayoutStatus::Failed,
            'canceled' => PayoutStatus::Failed,
            'denied' => PayoutStatus::Failed,
            // In flight.
            'processing' => PayoutStatus::Processing,
            'pending' => PayoutStatus::Processing,
            'submitted' => PayoutStatus::Processing,
        ];

        foreach ($cases as $provider => $expected) {
            $this->assertSame(
                $expected,
                PayoutStatus::fromProvider($provider),
                "Provider status [{$provider}] mapped incorrectly.",
            );
        }
    }

    public function test_unknown_or_null_status_maps_to_null(): void
    {
        $this->assertNull(PayoutStatus::fromProvider(null));
        $this->assertNull(PayoutStatus::fromProvider(''));
        $this->assertNull(PayoutStatus::fromProvider('something_new'));
    }

    public function test_mapping_is_case_sensitive_to_provider_contracts(): void
    {
        // Rails normalise before calling; an unexpected case is treated as unknown
        // rather than silently coerced.
        $this->assertNull(PayoutStatus::fromProvider('PAID'));
    }
}
