<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Database\Factories;

use Capell\ContentBlocks\Enums\LayoutTypeEnum;
use Capell\Core\Database\Factories\TypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentBlockTypeFactory extends TypeFactory
{
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'type' => LayoutTypeEnum::ContentBlock->value,
        ];
    }
}
