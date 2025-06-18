<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Widget>
 */
class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->realTextBetween(2, 60);

        return [
            'name' => $name,
            'key' => $this->faker->unique()->slug,
            'type_id' => fn () => Type::factory()
                ->type(LayoutTypeEnum::Widget->value)
                ->state(
                    fn (): array => [
                        'default' => ! Type::query()->where('type', LayoutTypeEnum::Widget)->default()->exists(),
                    ]
                ),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => $this->faker->dateTimeBetween('-5 month'),
        ];
    }
}
