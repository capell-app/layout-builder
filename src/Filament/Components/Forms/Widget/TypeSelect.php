<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\BlueprintSelect as BaseBlueprintSelect;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;

class TypeSelect extends BaseBlueprintSelect
{
    protected null|BlueprintSubjectEnum|string $type = LayoutTypeEnum::Widget->value;
}
