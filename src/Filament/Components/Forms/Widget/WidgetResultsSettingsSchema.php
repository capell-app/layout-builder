<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Filament\Forms;

class WidgetResultsSettingsSchema
{
    public static function make(): array
    {
        return [
            Forms\Components\Checkbox::make('with_author')
                ->label(__('capell-admin::form.author')),
            Forms\Components\Checkbox::make('with_children_count')
                ->label(__('capell-admin::form.children_count')),
            Forms\Components\Checkbox::make('with_image')
                ->label(__('capell-admin::form.image')),
            Forms\Components\Checkbox::make('with_date')
                ->label(__('capell-admin::form.published_date')),
            Forms\Components\Checkbox::make('with_summary')
                ->label(__('capell-admin::form.summary')),
            Forms\Components\Checkbox::make('with_link_text')
                ->label(__('capell-admin::form.link_text')),
            Forms\Components\Checkbox::make('with_parent')
                ->label(__('capell-admin::form.parent_page')),
            Forms\Components\Checkbox::make('with_tags')
                ->label(__('capell-admin::form.tags')),
        ];
    }
}
