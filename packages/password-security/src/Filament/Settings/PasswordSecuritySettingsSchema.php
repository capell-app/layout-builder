<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class PasswordSecuritySettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Fieldset::make(__('capell-password-security::settings.password_expiry'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('password_expiry_enabled')
                        ->label(__('capell-password-security::settings.password_expiry_enabled')),
                    TextInput::make('password_expiry_days')
                        ->label(__('capell-password-security::settings.password_expiry_days'))
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->visible(fn (callable $get): bool => (bool) $get('password_expiry_enabled')),
                ]),
            Fieldset::make(__('capell-password-security::settings.force_change'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('force_change_enabled')
                        ->label(__('capell-password-security::settings.force_change_enabled')),
                ]),
            Fieldset::make(__('capell-password-security::settings.password_safety'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('compromised_password_checks_enabled')
                        ->label(__('capell-password-security::settings.compromised_password_checks_enabled')),
                    Toggle::make('password_history_enabled')
                        ->label(__('capell-password-security::settings.password_history_enabled')),
                    TextInput::make('password_history_count')
                        ->label(__('capell-password-security::settings.password_history_count'))
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->visible(fn (callable $get): bool => (bool) $get('password_history_enabled')),
                ]),
        ];
    }
}
