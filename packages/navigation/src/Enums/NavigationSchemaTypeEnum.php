<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Capell\Admin\Concerns\HasSchemaTypes;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Navigation\Filament\Schemas\Navigations\DefaultNavigationSchema;

enum NavigationSchemaTypeEnum: string implements SchemaTypeEnumInterface
{
    use HasSchemaTypes;

    case Navigation = 'Navigations';

    /**
     * @return array<string, class-string>
     */
    public function getSchemas(): array
    {
        return match ($this) {
            self::Navigation => [
                'default' => DefaultNavigationSchema::class,
            ],
        };
    }
}
