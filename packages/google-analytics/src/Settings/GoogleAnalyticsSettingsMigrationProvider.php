<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Settings;

final class GoogleAnalyticsSettingsMigrationProvider
{
    /**
     * @return array<int, string>
     */
    public function getSettingMigrations(): array
    {
        return ['create_google_analytics_settings'];
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
