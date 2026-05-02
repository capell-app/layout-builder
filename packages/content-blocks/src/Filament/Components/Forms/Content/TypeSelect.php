<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\ContentBlocks\Enums\LayoutTypeEnum;
use Capell\Core\Enums\TypeEnum;

class TypeSelect extends BaseTypeSelect
{
    protected null|TypeEnum|string $type = LayoutTypeEnum::ContentBlock->value;
}
