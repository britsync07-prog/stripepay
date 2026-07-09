<?php

namespace Tests\Unit\Actions;

use App\Actions\Admin\StorePlan;
use App\Services\SettingsService;
use Tests\TestCase;

/**
 * Plan price is admin-entered in major units and stored as integer minor units.
 * It must convert via brick/money (exact decimal), never float arithmetic — that
 * price becomes the amount actually charged to the client.
 */
class StorePlanPayloadTest extends TestCase
{
    public function test_whole_and_two_decimal_prices_convert_exactly(): void
    {
        $this->assertSame(12000, StorePlan::payload(['price' => '120'])['price']);
        $this->assertSame(9999, StorePlan::payload(['price' => '99.99'])['price']);
        $this->assertSame(0, StorePlan::payload(['price' => '0'])['price']);
    }

    public function test_more_than_two_decimals_round_to_the_nearest_cent(): void
    {
        // Float `round(10.005 * 100)` yields 1000 (binary drift); brick/money
        // HALF_UP yields the correct 1001.
        $this->assertSame(1001, StorePlan::payload(['price' => '10.005'])['price']);
    }

    public function test_currency_is_forced_to_the_system_currency(): void
    {
        $this->assertSame(SettingsService::currencyCode(), StorePlan::payload(['price' => '10'])['currency']);
    }
}
