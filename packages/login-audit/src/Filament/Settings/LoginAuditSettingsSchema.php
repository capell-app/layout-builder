<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

final class LoginAuditSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('show_login_audits')
                            ->label(__('capell-login-audit::settings.show_login_audits')),
                        'capell-login-audit::settings.show_login_audits_helper',
                    ),
                    TextInput::make('retention_days')
                        ->label(__('capell-login-audit::settings.retention_days'))
                        ->helperText(__('capell-login-audit::settings.retention_days_helper'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Checkbox::make('track_user_ip_addresses')
                            ->label(__('capell-login-audit::settings.track_user_ip_addresses')),
                        'capell-login-audit::settings.track_user_ip_addresses_helper',
                    ),
                    HelperText::apply(
                        Toggle::make('enable_user_resource_bridge')
                            ->label(__('capell-login-audit::settings.enable_user_resource_bridge')),
                        'capell-login-audit::settings.enable_user_resource_bridge_helper',
                    ),
                ]),
        ];
    }
}
