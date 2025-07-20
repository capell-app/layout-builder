<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class ContainerWidthSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.container_width'))
            ->helperText(__('capell-admin::generic.container_width_helper'))
            ->options([
                'container' => __('capell-admin::generic.container'),
                'full' => __('capell-admin::generic.full'),
            ]);
    }
}
