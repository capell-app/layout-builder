<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\Grant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrowserToken>
 */
class BrowserTokenFactory extends Factory
{
    protected $model = BrowserToken::class;

    public function configure(): static
    {
        return $this->afterCreating(function (BrowserToken $browserToken): void {
            if ($browserToken->grant === null) {
                return;
            }

            if ($browserToken->access_area_id === $browserToken->grant->access_area_id) {
                return;
            }

            $browserToken
                ->forceFill(['access_area_id' => $browserToken->grant->access_area_id])
                ->save();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $area = Area::factory();

        return [
            'access_area_id' => $area,
            'grant_id' => Grant::factory()->for($area, 'area'),
            'token_hash' => hash('sha256', $this->faker->unique()->sha256()),
            'status' => BrowserTokenStatus::Active,
            'ip_hash' => hash('sha256', $this->faker->unique()->ipv4()),
            'user_agent' => $this->faker->userAgent(),
            'expires_at' => now()->addDays(180),
            'last_used_at' => now(),
            'revoked_at' => null,
            'metadata' => [],
        ];
    }
}
