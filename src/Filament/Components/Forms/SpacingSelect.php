<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class SpacingSelect extends Forms\Components\Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.spacing'))
            ->helperText(__('capell-admin::generic.spacing_help'))
            ->placeholder(__('capell-admin::form.none'))
            ->options([
                'sm' => __('capell-admin::generic.small'),
                'md' => __('capell-admin::generic.medium'),
                'lg' => __('capell-admin::generic.large'),
            ]);
    }
}
