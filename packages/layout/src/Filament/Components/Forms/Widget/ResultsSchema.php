<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ResultsSchema
{
    public static function make(Schema $schema): array
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
            ...ResultsOverrideSchema::make($schema),
            Grid::make()
                ->schema([
                    TextInput::make('columns')
                        ->label(__('capell-layout::form.columns'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(12),
                    SpacingSelect::make('spacing')
                        ->helperText(__('capell-admin::generic.results_spacing_help')),
                ]),
        ];
    }
}
