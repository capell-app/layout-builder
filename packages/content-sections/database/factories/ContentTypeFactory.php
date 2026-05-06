<?php

declare(strict_types=1);

namespace Capell\ContentSections\Database\Factories;

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\Core\Database\Factories\TypeFactory;

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
