<?php

declare(strict_types=1);

namespace Capell\ContentSections\Database\Factories;

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\Core\Database\Factories\TypeFactory;
use Illuminate\Support\Str;

class ContentTypeFactory extends TypeFactory
{
    public function definition(): array
    {
        return [
            'name' => 'Section',
            'key' => 'section-' . Str::random(8),
            'type' => LayoutTypeEnum::Section->value,
            'default' => false,
            'group' => null,
            'admin' => [
                'configurator' => 'Default',
            ],
            'created_at' => now()->subYear(),
            'updated_at' => now()->subMonths(5),
        ];
    }
}
