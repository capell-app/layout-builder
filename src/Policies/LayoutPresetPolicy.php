<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Policies;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Foundation\Auth\User;
use Throwable;

final class LayoutPresetPolicy
{
    use ResolvesShieldPermission;

    private const string SUBJECT = 'Layout';

    public function create(User $user, Site $site): bool
    {
        return $this->canManagePresetsForSite($user, $site);
    }

    public function apply(User $user, LayoutPreset $preset, Site $site): bool
    {
        return $preset->site_id === $site->getKey()
            && $this->canManagePresetsForSite($user, $site);
    }

    public function update(User $user, LayoutPreset $preset): bool
    {
        $site = $preset->site;

        return $site instanceof Site && $this->canManagePresetsForSite($user, $site);
    }

    public function delete(User $user, LayoutPreset $preset): bool
    {
        $site = $preset->site;

        return $site instanceof Site && $this->canManagePresetsForSite($user, $site);
    }

    private function canManagePresetsForSite(User $user, Site $site): bool
    {
        if ($user->getAuthIdentifier() === null || ! $site->exists) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (method_exists($user, 'getAssignedSiteIds') && $user->getAssignedSiteIds()->isNotEmpty() && ! $user->getAssignedSiteIds()->contains($site->getKey())) {
            return false;
        }

        foreach (['edit_layout', 'update', 'create'] as $ability) {
            $permission = self::permission($ability, self::SUBJECT);

            if (method_exists($user, 'hasPermissionForSite') && $this->allows(fn (): bool => $user->hasPermissionForSite($site, $permission))) {
                return true;
            }

            if ($this->allows(fn (): bool => $user->checkPermissionTo($permission))) {
                return true;
            }
        }

        return false;
    }

    private function isSuperAdmin(User $user): bool
    {
        return method_exists($user, 'hasRole')
            && $user->hasRole(config('capell.roles.super_admin', 'super_admin'));
    }

    private function allows(callable $callback): bool
    {
        try {
            return $callback() === true;
        } catch (Throwable) {
            return false;
        }
    }
}
