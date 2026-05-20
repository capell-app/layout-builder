<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Database\Factories\Concerns\HasMeta;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    use HasMeta;

    protected $model = Block::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->realTextBetween(2, 60);

        return [
            'name' => $name,
            'key' => fake()->unique()->slug(),
            'blueprint_id' => fn (): int => Blueprint::factory()
                ->type(LayoutTypeEnum::Block->value)
                ->state(
                    fn (): array => [
                        'default' => ! Blueprint::query()->where('type', LayoutTypeEnum::Block->value)->default()->exists(),
                    ],
                )
                ->create()
                ->getKey(),
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }
}
