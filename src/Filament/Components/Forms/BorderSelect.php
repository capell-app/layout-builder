<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\LayoutContainerBorderValue;
use Filament\Forms\Components\Select;

class BorderSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.border'))
            ->options(LayoutContainerBorderValue::class)
            ->placeholder(__('capell-layout-builder::form.theme_default'));
    }
}
