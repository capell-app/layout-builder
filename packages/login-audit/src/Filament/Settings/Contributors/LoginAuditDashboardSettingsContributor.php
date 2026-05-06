<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class LoginAuditDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'login_audits', 'label' => 'Authentication logs', 'group' => 'System health'],
        ];
    }
}
