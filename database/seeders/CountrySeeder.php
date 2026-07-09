<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/countries.json');

        if (! is_file($path)) {
            return;
        }

        $countries = json_decode((string) file_get_contents($path), true) ?: [];

        foreach (array_chunk($countries, 100) as $chunk) {
            Country::upsert(
                $chunk,
                uniqueBy: ['code'],
                update: ['name', 'dial_code', 'emoji'],
            );
        }
    }
}
