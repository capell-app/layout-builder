<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\FormBuilder\Components\ToggleButtons;

class ColorSchemeComponent extends ToggleButtons
{
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
