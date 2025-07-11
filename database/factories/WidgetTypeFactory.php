<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Database\Factories\TypeFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Widget>
 */
class WidgetTypeFactory extends TypeFactory
{
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'type' => LayoutTypeEnum::Widget->value,
        ];
    }
}
