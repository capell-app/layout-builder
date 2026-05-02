<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class SizeSelect extends Select
{
    protected function setUp(): void
    {
        $this->label(__('capell-mosaic::form.size'))
            ->options([
                'sm' => __('capell-admin::generic.small'),
                'md' => __('capell-admin::generic.medium'),
                'lg' => __('capell-admin::generic.large'),
            ]);
    }
}
