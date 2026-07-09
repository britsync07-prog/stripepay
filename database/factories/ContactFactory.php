<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'company' => $this->faker->optional()->company(),
            'country' => $this->faker->randomElement(['US', 'AE', 'GB', 'FR']),
            'phone' => $this->faker->optional()->e164PhoneNumber(),
        ];
    }
}
