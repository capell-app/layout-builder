<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Element;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;

class TypeSelect extends BaseTypeSelect
{
    protected null|BlueprintSubjectEnum|string $type = LayoutTypeEnum::Element->value;
}
