<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\DashboardReports;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Action;
use Spatie\Permission\Models\Role;

final class BuildPermissionAuditQueryAction extends Action
{
    public function handle(): Builder
    {
        return Role::query()
            ->whereNotIn('name', $this->systemRoleNames())
            ->withCount(['users', 'permissions'])
            ->orderBy('name');
    }

    /**
     * @return list<string>
     */
    private function systemRoleNames(): array
    {
        $configuredSuperAdminRole = config('capell.roles.super_admin', 'super_admin');
        $superAdminRole = is_string($configuredSuperAdminRole) && $configuredSuperAdminRole !== ''
            ? $configuredSuperAdminRole
            : 'super_admin';

        return array_values(array_unique([
            'super_admin',
            $superAdminRole,
            'system',
        ]));
    }
}
