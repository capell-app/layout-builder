<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class HeadingStyleSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::form.heading_style'))
            ->options([
                'secondary' => __('capell-admin::generic.secondary'),
            ]);
    }
}
