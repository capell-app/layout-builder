<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\Core\Enums\TypeEnum;
use Capell\Mosaic\Enums\LayoutTypeEnum;

class TypeSelect extends BaseTypeSelect
{
    protected null|TypeEnum|string $type = LayoutTypeEnum::Section->value;
}
