<?php

namespace Database\Seeders;

use App\Models\Expert;
use Illuminate\Database\Seeder;

class ExpertSeeder extends Seeder
{
    public function run(): void
    {
        $experts = [];

        foreach ($experts ?? [] as $expert) {
            Expert::firstOrCreate(['email' => $expert['email']], $expert);
        }
    }
}
