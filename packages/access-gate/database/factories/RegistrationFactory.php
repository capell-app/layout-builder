<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = $this->faker->unique()->safeEmail();

        return [
            'access_area_id' => Area::factory(),
            'email' => $email,
            'email_normalized' => strtolower($email),
            'single_registration_key' => null,
            'user_id' => null,
            'status' => RegistrationStatus::Pending,
            'requested_url' => $this->faker->url(),
            'requested_host' => $this->faker->domainName(),
            'position' => null,
            'field_values' => [],
            'metadata' => [],
            'requested_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'claimed_at' => null,
            'expired_at' => null,
        ];
    }
}
