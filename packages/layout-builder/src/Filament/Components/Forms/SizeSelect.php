<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\FormBuilder\Components\Select;

class SizeSelect extends Select
{
    protected function setUp(): void
    {
        $this->label(__('capell-layout-builder::form.size'))
            ->options([
                'sm' => __('capell-admin::generic.small'),
                'md' => __('capell-admin::generic.medium'),
                'lg' => __('capell-admin::generic.large'),
            ]);
    }
}
