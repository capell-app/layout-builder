<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\ToggleButtons;

class ColorSchemeComponent extends ToggleButtons
{
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.color'))
            ->inline()
            ->grouped()
            ->options([
                'auto' => __('capell-mosaic::generic.auto'),
                'light' => __('capell-mosaic::generic.light'),
                'dark' => __('capell-mosaic::generic.dark'),
            ]);
    }
}
