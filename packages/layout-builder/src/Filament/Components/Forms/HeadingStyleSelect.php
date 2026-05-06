<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\FormBuilder\Components\Select;

class HeadingStyleSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.heading_style'))
            ->options([
                'secondary' => __('capell-admin::generic.secondary'),
            ]);
    }
}
