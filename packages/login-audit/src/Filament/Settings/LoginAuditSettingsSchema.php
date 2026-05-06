<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\FormBuilder\Components\Checkbox;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class LoginAuditSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-login-audit::settings.fieldset'))
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
                ]),
        ];
    }
}
