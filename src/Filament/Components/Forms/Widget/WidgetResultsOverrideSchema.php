<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class WidgetResultsOverrideSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-layout::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
            Checkbox::make('show_page_content')
                ->label(__('capell-layout::form.show_page_content'))
                ->helperText(__('capell-admin::generic.show_page_content_info')),
            Checkbox::make('hide_no_results')
                ->label(__('capell-layout::form.hide_no_results'))
                ->helperText(__('capell-layout::generic.hide_no_results_info')),
        ];
    }
}
