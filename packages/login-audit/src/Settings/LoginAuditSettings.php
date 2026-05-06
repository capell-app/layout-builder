<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\LoginAudit\Filament\Settings\LoginAuditSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class LoginAuditSettings extends Settings implements SettingsContract
{
    public bool $show_login_audits = true;

    public int $retention_days = 90;

    public bool $track_user_ip_addresses = true;

    public static function group(): string
    {
        return 'login_audit';
    }

    public static function schema(): string
    {
        return LoginAuditSettingsSchema::class;
    }
}
