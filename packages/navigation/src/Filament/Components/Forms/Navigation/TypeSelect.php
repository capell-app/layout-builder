<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms\Navigation;

use Capell\Admin\Filament\Components\Forms\TypeSelect as BaseTypeSelect;
use Capell\Core\Enums\TypeEnum;

class TypeSelect extends BaseTypeSelect
{
    // 'navigation' is not a core TypeEnum case; use the string value directly
    protected null|TypeEnum|string $type = 'navigation';
}
