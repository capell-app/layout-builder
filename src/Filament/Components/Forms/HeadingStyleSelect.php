<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class HeadingStyleSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::form.heading_style'))
            ->options([
                'secondary' => __('capell-admin::generic.secondary'),
            ]);
    }
}
