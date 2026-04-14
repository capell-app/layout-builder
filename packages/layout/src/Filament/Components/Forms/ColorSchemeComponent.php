<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\ToggleButtons;

class ColorSchemeComponent extends ToggleButtons
{
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.color'))
            ->inline()
            ->grouped()
            ->options([
                'auto' => __('capell-layout::generic.auto'),
                'light' => __('capell-layout::generic.light'),
                'dark' => __('capell-layout::generic.dark'),
            ]);
    }
}
