<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Database\Factories\Concerns\HasMeta;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Element>
 */
class ElementFactory extends Factory
{
    use HasMeta;

    protected $model = Element::class;

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
                ->type(LayoutTypeEnum::Element->value)
                ->state(
                    fn (): array => [
                        'default' => ! Blueprint::query()->where('type', LayoutTypeEnum::Element->value)->default()->exists(),
                    ],
                )
                ->create()
                ->getKey(),
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }
}
