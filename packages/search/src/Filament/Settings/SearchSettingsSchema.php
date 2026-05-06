<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\Search\Enums\SearchDriver;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class SearchSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-search::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-search::settings.enabled')),
                        'capell-search::settings.enabled_helper',
                    ),
                    HelperText::apply(
                        Toggle::make('show_header_search')
                            ->label(__('capell-search::settings.show_header_search')),
                        'capell-search::settings.show_header_search_helper',
                    ),
                    Select::make('driver')
                        ->label(__('capell-search::settings.driver'))
                        ->options(SearchDriver::class)
                        ->required(),
                    TextInput::make('results_per_page')
                        ->label(__('capell-search::settings.results_per_page'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(50),
                    HelperText::apply(
                        Toggle::make('record_search_logs')
                            ->label(__('capell-search::settings.record_search_logs')),
                        'capell-search::settings.record_search_logs_helper',
                    ),
                    TextInput::make('log_retention_days')
                        ->label(__('capell-search::settings.log_retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Toggle::make('hash_visitor_data')
                            ->label(__('capell-search::settings.hash_visitor_data')),
                        'capell-search::settings.hash_visitor_data_helper',
                    ),
                    TextInput::make('minimum_query_length')
                        ->label(__('capell-search::settings.minimum_query_length'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10),
                ]),
        ];
    }
}
