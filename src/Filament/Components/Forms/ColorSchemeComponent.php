<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\ColorScheme;
use Filament\Forms\Components\ToggleButtons;
use Override;

class ColorSchemeComponent extends ToggleButtons
{
    #[Override]
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.color'))
            ->inline()
            ->grouped()
            ->options(ColorScheme::class);
    }
}
