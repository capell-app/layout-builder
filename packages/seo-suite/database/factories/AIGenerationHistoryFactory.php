<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Database\Factories;

use Capell\SeoSuite\Models\AIGenerationHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIGenerationHistory>
 */
class AIGenerationHistoryFactory extends Factory
{
    protected $model = AIGenerationHistory::class;

    public function definition(): array
    {
        return [
            'action' => 'GeneratePageTitleAction',
            'model' => 'gpt-4-turbo',
            'input' => $this->faker->sentence(),
            'output' => $this->faker->sentence(),
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'duration' => $this->faker->randomFloat(2, 0.01, 2.0),
            'metadata' => ['test' => true],
        ];
    }
}
