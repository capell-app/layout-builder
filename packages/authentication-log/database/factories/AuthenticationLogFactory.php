<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Database\Factories;

use Capell\AuthenticationLog\Models\AuthenticationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthenticationLog>
 */
class AuthenticationLogFactory extends Factory
{
    protected $model = AuthenticationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var class-string $userModel */
        $userModel = config('auth.providers.users.model');

        return [
            'authenticatable_type' => (new $userModel)->getMorphClass(),
            'authenticatable_id' => $userModel::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'login_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'login_successful' => $this->faker->boolean(90),
            'logout_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'cleared_by_user' => $this->faker->boolean(10),
            'location' => $this->faker->boolean() ? [
                'country' => $this->faker->country(),
                'city' => $this->faker->city(),
            ] : null,
        ];
    }
}
