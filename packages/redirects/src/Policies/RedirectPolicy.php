<?php

declare(strict_types=1);

namespace Capell\Redirects\Policies;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\PageUrl;
use Illuminate\Foundation\Auth\User;

/**
 * Redirect records are stored in the PageUrl model; Shield generates
 * permissions using the class basename of the resource's model, so the
 * subject here is "PageUrl", not "Redirect".
 *
 * Permission names are built via Shield so they match the host app's
 * `config/filament-shield.php` (case + separator).
 */
class RedirectPolicy
{
    use ResolvesShieldPermission;

    private const SUBJECT = 'PageUrl';

    public function viewAny(User $user): bool
    {
        if ($user->checkPermissionTo(self::permission('view_any', self::SUBJECT))) {
            return true;
        }

        return $user->checkPermissionTo(self::permission('view', self::SUBJECT));
    }

    public function view(User $user, PageUrl $record): bool
    {
        if ($user->checkPermissionTo(self::permission('view_any', self::SUBJECT))) {
            return $this->canAccessRecord($user, $record);
        }

        return $user->checkPermissionTo(self::permission('view', self::SUBJECT))
            && $this->canAccessRecord($user, $record);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('create', self::SUBJECT));
    }

    public function update(User $user, PageUrl $record): bool
    {
        return $user->checkPermissionTo(self::permission('update', self::SUBJECT))
            && $this->canAccessRecord($user, $record);
    }

    public function delete(User $user, PageUrl $record): bool
    {
        return $user->checkPermissionTo(self::permission('delete', self::SUBJECT))
            && $this->canAccessRecord($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('delete_any', self::SUBJECT));
    }

    public function restore(User $user, PageUrl $record): bool
    {
        return $user->checkPermissionTo(self::permission('restore', self::SUBJECT))
            && $this->canAccessRecord($user, $record);
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('restore_any', self::SUBJECT));
    }

    public function forceDelete(User $user, PageUrl $record): bool
    {
        return $user->checkPermissionTo(self::permission('force_delete', self::SUBJECT))
            && $this->canAccessRecord($user, $record);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('force_delete_any', self::SUBJECT));
    }

    /**
     * Import redirects is a custom ability — register the resolved permission
     * name (e.g. "Import:PageUrl") in config/filament-shield.php custom_permissions
     * for your host app.
     */
    public function import(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('import', self::SUBJECT));
    }

    /**
     * Export redirects is a custom ability — register the resolved permission
     * name (e.g. "Export:PageUrl") in config/filament-shield.php custom_permissions
     * for your host app.
     */
    public function export(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('export', self::SUBJECT));
    }

    private function canAccessRecord(User $user, PageUrl $record): bool
    {
        if (! method_exists($user, 'getAssignedSiteIds') && ! method_exists($user, 'isGlobalAdmin')) {
            return true;
        }

        $record->loadMissing('site');

        return $record->site !== null && SiteScope::actorCanUseSite($user, $record->site);
    }
}
