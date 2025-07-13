<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class AlignSelect extends Forms\Components\Select
{
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.align'))
            ->options([
                'left' => __('capell-admin::generic.left'),
                'right' => __('capell-admin::generic.right'),
                'center' => __('capell-admin::generic.center'),
            ]);
    }
}
