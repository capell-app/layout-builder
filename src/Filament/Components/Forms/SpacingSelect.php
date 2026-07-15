<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\WidgetBasicSpacingValue;
use Filament\Forms\Components\Select;

class SpacingSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.spacing'))
            ->options(WidgetBasicSpacingValue::class)
            ->placeholder(__('capell-layout-builder::form.theme_default'));
    }
}
