<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Filament\Forms\Components\Select;

class ResponsiveLayoutPatternSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.responsive_layout_pattern'))
            ->helperText(__('capell-layout-builder::generic.responsive_layout_pattern_helper'))
            ->options(ResponsiveLayoutPattern::class)
            ->placeholder(__('capell-layout-builder::form.responsive_layout_pattern_inherit'))
            ->live()
            ->selectablePlaceholder();
    }
}
