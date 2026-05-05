<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class GoogleAnalyticsSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-google-analytics::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-google-analytics::settings.enabled')),
                        'capell-google-analytics::settings.enabled_helper',
                    ),
                    TextInput::make('property_id')
                        ->label(__('capell-google-analytics::settings.property_id'))
                        ->helperText(__('capell-google-analytics::settings.property_id_helper')),
                    TextInput::make('credentials_path')
                        ->label(__('capell-google-analytics::settings.credentials_path'))
                        ->helperText(__('capell-google-analytics::settings.credentials_path_helper')),
                    TextInput::make('sync_days')
                        ->label(__('capell-google-analytics::settings.sync_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    TextInput::make('route_slug')
                        ->label(__('capell-google-analytics::settings.route_slug'))
                        ->required(),
                ]),
        ];
    }
}
