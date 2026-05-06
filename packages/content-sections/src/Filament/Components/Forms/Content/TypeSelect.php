<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\Core\Enums\TypeEnum;

class TypeSelect extends BaseTypeSelect
{
    protected null|TypeEnum|string $type = LayoutTypeEnum::Section->value;
}
