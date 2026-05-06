<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Filament\Pages;

use Capell\Admin\Filament\Pages\AbstractPackageSettingsPage;

class PasswordPolicySettingsPage extends AbstractPackageSettingsPage
{
    protected static string $settingsGroup = 'password_policy';

    protected static ?string $slug = 'password-policy/settings';
}
