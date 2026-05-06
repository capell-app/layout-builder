<?php

declare(strict_types=1);

namespace Capell\Insights\Settings;

use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;

final class InsightsSettingsMigrationProvider implements SettingsMigrationProviderInterface
{
    /**
     * @return array<int, string>
     */
    public function getSettingMigrations(): array
    {
        return ['create_insights_settings'];
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
