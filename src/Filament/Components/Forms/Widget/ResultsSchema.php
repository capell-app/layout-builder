<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget;

use Capell\Mosaic\Filament\Components\Forms\SpacingSelect;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ResultsSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Checkbox::make('with_author')
                ->label(__('capell-mosaic::form.author')),
            Checkbox::make('with_children_count')
                ->label(__('capell-mosaic::form.children_count')),
            Checkbox::make('with_image')
                ->label(__('capell-admin::form.image')),
            Checkbox::make('with_date')
                ->label(__('capell-mosaic::form.published_date')),
            Checkbox::make('with_summary')
                ->label(__('capell-admin::form.summary')),
            Checkbox::make('with_link_text')
                ->label(__('capell-admin::form.link_text')),
            Checkbox::make('with_parent')
                ->label(__('capell-admin::form.parent_page')),
            ...ResultsOverrideSchema::make($configurator),
            Grid::make()
                ->schema([
                    TextInput::make('columns')
                        ->label(__('capell-mosaic::form.columns'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(12),
                    SpacingSelect::make('spacing')
                        ->helperText(__('capell-admin::generic.results_spacing_help')),
                ]),
        ];
    }
}
