<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Settings;

use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;

class DefaultThemeSettingsMigrationProvider implements SettingsMigrationProviderInterface
{
    public function getSettingMigrations(): array
    {
        return ['create_default_theme_settings'];
    }

    public function migrations(): array
    {
        return $this->getSettingMigrations();
    }
}
