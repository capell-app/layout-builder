<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\WidgetSizeValue;
use Filament\Forms\Components\Select;
use Override;

class SizeSelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        $this->label(__('capell-layout-builder::form.size'))
            ->options(WidgetSizeValue::class);
    }
}
