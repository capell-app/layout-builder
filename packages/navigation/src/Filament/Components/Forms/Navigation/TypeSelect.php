<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms\Navigation;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\Core\Enums\TypeEnum;

class TypeSelect extends BaseTypeSelect
{
    protected null|TypeEnum|string $type = TypeEnum::Navigation;
}
