<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Database\Factories\TypeFactory;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentTypeFactory extends TypeFactory
{
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'type' => LayoutTypeEnum::Section->value,
        ];
    }
}
