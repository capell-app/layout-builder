<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\ToggleButtons;

class ColorSchemeComponent extends ToggleButtons
{
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.color_scheme'))
            ->inline()
            ->grouped()
            ->options([
                '' => __('capell-admin::generic.auto'),
                'light' => __('capell-admin::generic.light'),
                'dark' => __('capell-admin::generic.dark'),
            ]);
    }
}
