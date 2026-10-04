<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Policies;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\Admin\Support\SiteScope;
use Illuminate\Foundation\Auth\User;
use Throwable;

/**
 * Widgets are a global library with no owning site, so access follows the
 * Shield permission for each ability; a global actor may do everything.
 * Registering this policy keeps the widget publish panel authorisable in an
 * application that does not ship its own Widget policy.
 */
final class WidgetPolicy
{
    use ResolvesShieldPermission;

    private const string SUBJECT = 'Widget';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_any');
    }

    public function view(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(User $user): bool
    {
        return $this->can($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->can($user, 'delete_any');
    }

    public function restore(User $user): bool
    {
        return $this->can($user, 'restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->can($user, 'restore_any');
    }

    public function forceDelete(User $user): bool
    {
        return $this->can($user, 'force_delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->can($user, 'force_delete_any');
    }

    public function replicate(User $user): bool
    {
        return $this->can($user, 'replicate');
    }

    public function reorder(User $user): bool
    {
        return $this->can($user, 'reorder');
    }

    private function can(User $user, string $ability): bool
    {
        if ($user->getAuthIdentifier() === null) {
            return false;
        }

        if (SiteScope::isGlobalActor($user)) {
            return true;
        }

        try {
            return $user->checkPermissionTo(self::permission($ability, self::SUBJECT)) === true;
        } catch (Throwable) {
            return false;
        }
    }
}
