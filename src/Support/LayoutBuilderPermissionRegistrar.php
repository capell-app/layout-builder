<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Spatie\Permission\Models\Permission;

final class LayoutBuilderPermissionRegistrar
{
    use ResolvesShieldPermission;

    private const SUBJECT = 'Layout';

    /**
     * @return list<string>
     */
    public static function permissionsForRole(string $roleName): array
    {
        return match ($roleName) {
            'editor' => [
                self::permission('view_any', self::SUBJECT),
                self::permission('view', self::SUBJECT),
                self::permission('edit_content', self::SUBJECT),
            ],
            'admin' => [
                self::permission('view_any', self::SUBJECT),
                self::permission('view', self::SUBJECT),
                self::permission('create', self::SUBJECT),
                self::permission('edit_content', self::SUBJECT),
                self::permission('edit_layout', self::SUBJECT),
                self::permission('update', self::SUBJECT),
                self::permission('delete', self::SUBJECT),
                self::permission('delete_any', self::SUBJECT),
                self::permission('restore', self::SUBJECT),
                self::permission('force_delete', self::SUBJECT),
                self::permission('replicate', self::SUBJECT),
                self::permission('reorder', self::SUBJECT),
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function existingPermissionsForRole(string $roleName, string $guardName): array
    {
        $permissions = self::permissionsForRole($roleName);

        if ($permissions === []) {
            return [];
        }

        return Permission::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }
}
