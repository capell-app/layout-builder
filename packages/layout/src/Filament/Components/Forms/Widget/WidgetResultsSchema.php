<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;

class WidgetResultsSchema
{
    public static function make(): array
    {
        return [
            Checkbox::make('with_author')
                ->label(__('capell-layout::form.author')),
            Checkbox::make('with_children_count')
                ->label(__('capell-layout::form.children_count')),
            Checkbox::make('with_image')
                ->label(__('capell-admin::form.image')),
            Checkbox::make('with_date')
                ->label(__('capell-layout::form.published_date')),
            Checkbox::make('with_summary')
                ->label(__('capell-admin::form.summary')),
            Checkbox::make('with_link_text')
                ->label(__('capell-admin::form.link_text')),
            Checkbox::make('with_parent')
                ->label(__('capell-admin::form.parent_page')),
            TextInput::make('columns')
                ->label(__('capell-layout::form.columns'))
                ->numeric()
                ->minValue(0)
                ->maxValue(12),
        ];
    }
}
