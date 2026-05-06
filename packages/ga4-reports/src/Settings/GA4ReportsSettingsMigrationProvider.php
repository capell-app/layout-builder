<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Settings;

final class GA4ReportsSettingsMigrationProvider
{
    /**
     * @return array<int, string>
     */
    public function getSettingMigrations(): array
    {
        return ['create_ga4_reports_settings'];
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
