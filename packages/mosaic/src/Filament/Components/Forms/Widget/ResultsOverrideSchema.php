<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class ResultsOverrideSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-mosaic::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
            Checkbox::make('show_page_content')
                ->label(__('capell-mosaic::form.show_page_content'))
                ->helperText(__('capell-admin::generic.show_page_content_info')),
            Checkbox::make('hide_no_results')
                ->label(__('capell-mosaic::form.hide_no_results'))
                ->helperText(__('capell-mosaic::generic.hide_no_results_info')),
        ];
    }
}
