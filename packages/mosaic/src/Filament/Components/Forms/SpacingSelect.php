<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class SpacingSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::form.spacing'))
            ->options([
                'none' => __('capell-admin::generic.none'),
                'sm' => __('capell-admin::generic.small'),
                'md' => __('capell-admin::generic.medium'),
                'lg' => __('capell-admin::generic.large'),
            ]);
    }
}
