<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class PaddingSelect extends Forms\Components\Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.padding'))
            ->multiple()
            ->options([
                '' => __('capell-admin::form.none'),
                'sm' => __('capell-admin::generic.small'),
                't-sm' => __('capell-admin::generic.small_top'),
                'b-sm' => __('capell-admin::generic.small_bottom'),
                'md' => __('capell-admin::generic.medium'),
                't-md' => __('capell-admin::generic.medium_top'),
                'b-md' => __('capell-admin::generic.medium_bottom'),
                'lg' => __('capell-admin::generic.large'),
                't-lg' => __('capell-admin::generic.large_top'),
                'b-lg' => __('capell-admin::generic.large_bottom'),
            ]);
    }
}
