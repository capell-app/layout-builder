<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Filament\Pages;

use Capell\Admin\Filament\Pages\AbstractPackageSettingsPage;

class PasswordSecuritySettingsPage extends AbstractPackageSettingsPage
{
    protected static string $settingsGroup = 'password_security';

    protected static ?string $slug = 'password-security/settings';
}
