<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class ColorSchemeComponent extends Forms\Components\ToggleButtons
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
