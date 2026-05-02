<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Settings;

use Capell\AuthenticationLog\Filament\Settings\AuthenticationLogSettingsSchema;
use Capell\Core\Contracts\SettingsContract;
use Spatie\LaravelSettings\Settings;

final class AuthenticationLogSettings extends Settings implements SettingsContract
{
    public bool $show_authentication_logs = true;

    public int $retention_days = 90;

    public bool $track_user_ip_addresses = true;

    public static function group(): string
    {
        return 'authentication_log';
    }

    public static function schema(): string
    {
        return AuthenticationLogSettingsSchema::class;
    }
}
