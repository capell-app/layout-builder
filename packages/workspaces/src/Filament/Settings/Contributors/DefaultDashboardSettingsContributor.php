<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class DefaultDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'setup_health', 'label' => 'Setup health', 'group' => 'Setup'],
            ['key' => 'my_work_queue', 'label' => 'My work queue', 'group' => 'Editor'],
            ['key' => 'recently_published', 'label' => 'Recently published', 'group' => 'Editor'],
            ['key' => 'content_health', 'label' => 'Content health', 'group' => 'Editor'],
            ['key' => 'site_traffic', 'label' => 'Site traffic', 'group' => 'Admin'],
            ['key' => 'top_pages', 'label' => 'Top pages', 'group' => 'Admin'],
            ['key' => 'cache_health', 'label' => 'Cache health', 'group' => 'Admin'],
            ['key' => 'content_scheduler', 'label' => 'Content scheduler', 'group' => 'Editor'],
            ['key' => 'workspace_activity', 'label' => 'Workspace activity', 'group' => 'Admin'],
        ];
    }
}
