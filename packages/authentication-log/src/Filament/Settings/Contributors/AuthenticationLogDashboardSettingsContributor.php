<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class AuthenticationLogDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'authentication_logs', 'label' => 'Authentication logs', 'group' => 'System health'],
        ];
    }
}
