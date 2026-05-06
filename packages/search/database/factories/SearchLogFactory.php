<?php

declare(strict_types=1);

namespace Capell\Search\Database\Factories;

use Capell\Search\Models\SearchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchLog>
 */
class SearchLogFactory extends Factory
{
    protected $model = SearchLog::class;

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
