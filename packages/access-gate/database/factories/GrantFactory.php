<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Grant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grant>
 */
class GrantFactory extends Factory
{
    protected $model = Grant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_area_id' => Area::factory(),
            'registration_id' => null,
            'subject_type' => GrantSubjectType::Email,
            'subject_id' => null,
            'user_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'status' => GrantStatus::Active,
            'starts_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
            'discount_label' => null,
            'discount_code' => null,
            'discount_expires_at' => null,
            'discount_metadata' => [],
            'metadata' => [],
        ];
    }
}
