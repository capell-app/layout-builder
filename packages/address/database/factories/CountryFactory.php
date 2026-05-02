<?php

declare(strict_types=1);

namespace Capell\Address\Database\Factories;

use Capell\Address\Models\Country;
use Faker\Provider\en_US\Address;
use Faker\Provider\Miscellaneous;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        $this->faker->addProvider(new Address($this->faker));
        $this->faker->addProvider(new Miscellaneous($this->faker));

        return [
            'name' => $this->faker->country(),
            'iso2' => strtoupper($this->faker->unique()->lexify('??')),
            'iso3' => strtoupper($this->faker->unique()->lexify('???')),
            'language_id' => null,
        ];
    }
}
