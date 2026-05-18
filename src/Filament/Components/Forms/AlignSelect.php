<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\Forms\Components\Select;
use Override;

class AlignSelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        $this->label(__('capell-layout-builder::form.align'))
            ->options([
                'left' => __('capell-admin::generic.left'),
                'right' => __('capell-admin::generic.right'),
                'center' => __('capell-admin::generic.center'),
            ]);
    }
}
