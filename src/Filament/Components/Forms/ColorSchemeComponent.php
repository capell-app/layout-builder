<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

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
            ->options([
                'auto' => __('capell-layout-builder::generic.auto'),
                'light' => __('capell-layout-builder::generic.light'),
                'dark' => __('capell-layout-builder::generic.dark'),
            ]);
    }
}
