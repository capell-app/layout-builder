<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Settings;

use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;

class FoundationThemeSettingsMigrationProvider implements SettingsMigrationProviderInterface
{
    public function getSettingMigrations(): array
    {
        return ['create_foundation_theme_settings'];
    }

    public function migrations(): array
    {
        return $this->getSettingMigrations();
    }
}
