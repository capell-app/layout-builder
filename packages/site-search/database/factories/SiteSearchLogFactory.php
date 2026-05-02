<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Database\Factories;

use Capell\SiteSearch\Models\SiteSearchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSearchLog>
 */
class SiteSearchLogFactory extends Factory
{
    protected $model = SiteSearchLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $query = $this->faker->words(3, true);

        return [
            'site_id' => null,
            'language_id' => null,
            'query' => $query,
            'normalized_query' => mb_strtolower($query),
            'results_count' => $this->faker->numberBetween(0, 25),
            'clicked_result_url' => null,
            'ip_hash' => hash('sha256', '203.0.113.10|' . config('app.key')),
            'user_agent_hash' => hash('sha256', 'Capell Test Browser|' . config('app.key')),
            'searched_at' => now()->toImmutable(),
        ];
    }
}
