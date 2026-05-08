<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\ClaimToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimToken>
 */
class ClaimTokenFactory extends Factory
{
    protected $model = ClaimToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_area_id' => Area::factory(),
            'registration_id' => null,
            'grant_id' => null,
            'token_hash' => hash('sha256', $this->faker->unique()->sha256()),
            'status' => ClaimTokenStatus::Active,
            'expires_at' => now()->addDays(7),
            'consumed_at' => null,
            'metadata' => [],
        ];
    }
}
