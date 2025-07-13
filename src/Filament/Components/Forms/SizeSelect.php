<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class SizeSelect extends Forms\Components\Select
{
    protected function setUp(): void
    {
        $this->label(__('capell-admin::form.size'))
            ->options([
                'sm' => __('capell-admin::generic.small'),
                'md' => __('capell-admin::generic.medium'),
                'lg' => __('capell-admin::generic.large'),
            ]);
    }
}
