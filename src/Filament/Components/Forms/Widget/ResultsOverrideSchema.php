<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ResultsOverrideSchema
{
    /**
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
     */
    public static function make(Schema $configurator): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-layout-builder::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
            Checkbox::make('show_page_content')
                ->label(__('capell-layout-builder::form.show_page_content'))
                ->helperText(__('capell-admin::generic.show_page_content_info')),
            Checkbox::make('hide_no_results')
                ->label(__('capell-layout-builder::form.hide_no_results'))
                ->helperText(__('capell-layout-builder::generic.hide_no_results_info')),
        ];
    }
}
