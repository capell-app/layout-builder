<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class AuthenticationLogSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-authentication-log::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('show_authentication_logs')
                            ->label(__('capell-authentication-log::settings.show_authentication_logs')),
                        'capell-authentication-log::settings.show_authentication_logs_helper',
                    ),
                    TextInput::make('retention_days')
                        ->label(__('capell-authentication-log::settings.retention_days'))
                        ->helperText(__('capell-authentication-log::settings.retention_days_helper'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Checkbox::make('track_user_ip_addresses')
                            ->label(__('capell-authentication-log::settings.track_user_ip_addresses')),
                        'capell-authentication-log::settings.track_user_ip_addresses_helper',
                    ),
                ]),
        ];
    }
}
