<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\Type\TypeSelect;
use Capell\Core\Enums\TypeEnum;
use Capell\Layout\Enums\LayoutTypeEnum;

class ContentTypeSelect extends TypeSelect
{
    protected null|TypeEnum|string $type = LayoutTypeEnum::Content->value;
}
