<?php

declare(strict_types=1);

namespace Capell\Analytics\Settings;

use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;

final class AnalyticsSettingsMigrationProvider implements SettingsMigrationProviderInterface
{
    /**
     * @return array<int, string>
     */
    public function getSettingMigrations(): array
    {
        return ['create_analytics_settings'];
    }

    /**
     * @return array<int, string>
     */
    public function migrations(): array
    {
        return $this->getSettingMigrations();
    }

    public function path(): string
    {
        return dirname(__DIR__, 2) . '/database/settings';
    }
}
