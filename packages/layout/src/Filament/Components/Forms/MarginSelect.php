<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class MarginSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::form.margin'))
            ->multiple()
            ->options([
                '' => __('capell-layout::form.none'),
                'sm' => __('capell-admin::generic.small'),
                't-sm' => __('capell-admin::generic.small_top'),
                'b-sm' => __('capell-admin::generic.small_bottom'),
                'md' => __('capell-admin::generic.medium'),
                't-md' => __('capell-admin::generic.medium_top'),
                'b-md' => __('capell-admin::generic.medium_bottom'),
                'lg' => __('capell-admin::generic.large'),
                't-lg' => __('capell-admin::generic.large_top'),
                'b-lg' => __('capell-admin::generic.large_bottom'),
                'xl' => __('capell-admin::generic.extra_large'),
                't-xl' => __('capell-admin::generic.extra_large_top'),
                'b-xl' => __('capell-admin::generic.extra_large_bottom'),
            ]);
    }
}
