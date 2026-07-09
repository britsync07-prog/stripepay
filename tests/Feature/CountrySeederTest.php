<?php

namespace Tests\Feature;

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountrySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_every_country(): void
    {
        $this->seed(CountrySeeder::class);

        $this->assertGreaterThanOrEqual(240, Country::count());
        $this->assertSame('France', Country::where('code', 'FR')->value('name'));
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(CountrySeeder::class);
        $first = Country::count();

        $this->seed(CountrySeeder::class);

        $this->assertSame($first, Country::count());
    }

    public function test_options_are_value_label_pairs_sorted_by_name(): void
    {
        $this->seed(CountrySeeder::class);

        $options = Country::options();

        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);

        $labels = array_column($options, 'label');
        $sorted = $labels;
        sort($sorted);
        $this->assertSame($sorted, $labels);
    }
}
