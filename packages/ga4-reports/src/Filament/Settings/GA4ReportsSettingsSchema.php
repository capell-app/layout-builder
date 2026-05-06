<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class GA4ReportsSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-ga4-reports::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-ga4-reports::settings.enabled')),
                        'capell-ga4-reports::settings.enabled_helper',
                    ),
                    TextInput::make('property_id')
                        ->label(__('capell-ga4-reports::settings.property_id'))
                        ->helperText(__('capell-ga4-reports::settings.property_id_helper')),
                    TextInput::make('credentials_path')
                        ->label(__('capell-ga4-reports::settings.credentials_path'))
                        ->helperText(__('capell-ga4-reports::settings.credentials_path_helper')),
                    TextInput::make('sync_days')
                        ->label(__('capell-ga4-reports::settings.sync_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    TextInput::make('route_slug')
                        ->label(__('capell-ga4-reports::settings.route_slug'))
                        ->required(),
                ]),
        ];
    }
}
