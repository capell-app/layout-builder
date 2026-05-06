<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Policies;

use Capell\MigrationAssistant\Actions\InstallMigrationAssistantPermissionsAction;
use Capell\MigrationAssistant\Models\ImportSession;
use Illuminate\Foundation\Auth\User;

class ImportSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isGlobalAdmin($user)
            && $user->checkPermissionTo(InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_VIEW);
    }

    public function view(User $user, ImportSession $importSession): bool
    {
        return $this->isGlobalAdmin($user)
            && $user->checkPermissionTo(InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_VIEW);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ImportSession $importSession): bool
    {
        return false;
    }

    public function delete(User $user, ImportSession $importSession): bool
    {
        return false;
    }

    private function isGlobalAdmin(User $user): bool
    {
        if (method_exists($user, 'isGlobalAdmin')) {
            return $user->isGlobalAdmin();
        }

        $superAdminRole = config('capell.roles.super_admin', 'super_admin');

        return is_string($superAdminRole)
            && $superAdminRole !== ''
            && method_exists($user, 'hasRole')
            && $user->hasRole($superAdminRole);
    }
}
