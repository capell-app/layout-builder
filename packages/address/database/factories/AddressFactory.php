<?php

declare(strict_types=1);

namespace Capell\Address\Database\Factories;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Faker\Provider\en_US\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        // Add required providers to avoid Unknown format exceptions when running tests in parallel.
        $this->faker->addProvider(new Person($this->faker));
        $this->faker->addProvider(new \Faker\Provider\en_US\Address($this->faker));

        return [
            'name' => $this->faker->name(),
            'line1' => $this->faker->streetAddress(),
            'line2' => null,
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_id' => Country::factory(),
            'meta' => [
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
            ],
        ];
    }
}
