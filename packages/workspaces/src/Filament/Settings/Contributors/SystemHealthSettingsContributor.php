<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class SystemHealthSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'registry_health', 'label' => 'Registry health', 'group' => 'System health'],
            ['key' => 'migrations_health', 'label' => 'Migrations health', 'group' => 'System health'],
            ['key' => 'packages_installed', 'label' => 'Packages installed', 'group' => 'System health'],
            ['key' => 'tailwind_build_status', 'label' => 'Tailwind build status', 'group' => 'System health'],
            ['key' => 'workspace_merge_history', 'label' => 'Workspace merge history', 'group' => 'System health'],
        ];
    }
}
