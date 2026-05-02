<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\SiteSearch\Enums\SearchDriver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class SiteSearchSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-site-search::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-site-search::settings.enabled')),
                        'capell-site-search::settings.enabled_helper',
                    ),
                    HelperText::apply(
                        Toggle::make('show_header_search')
                            ->label(__('capell-site-search::settings.show_header_search')),
                        'capell-site-search::settings.show_header_search_helper',
                    ),
                    Select::make('driver')
                        ->label(__('capell-site-search::settings.driver'))
                        ->options(SearchDriver::class)
                        ->required(),
                    TextInput::make('results_per_page')
                        ->label(__('capell-site-search::settings.results_per_page'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(50),
                    HelperText::apply(
                        Toggle::make('record_search_logs')
                            ->label(__('capell-site-search::settings.record_search_logs')),
                        'capell-site-search::settings.record_search_logs_helper',
                    ),
                    TextInput::make('log_retention_days')
                        ->label(__('capell-site-search::settings.log_retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Toggle::make('hash_visitor_data')
                            ->label(__('capell-site-search::settings.hash_visitor_data')),
                        'capell-site-search::settings.hash_visitor_data_helper',
                    ),
                    TextInput::make('minimum_query_length')
                        ->label(__('capell-site-search::settings.minimum_query_length'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10),
                ]),
        ];
    }
}
