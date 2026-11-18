<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\LayoutBuilder\Manifest\LayoutBuilderPermissionsContribution;
use Spatie\Permission\Models\Permission;

final class LayoutBuilderPermissionRegistrar
{
    use ResolvesShieldPermission;

    public const string SUBJECT = 'Layout';

    public const string WIDGET_SUBJECT = 'Widget';

    /**
     * The abilities WidgetPolicy checks, so roles and installers can grant widget access.
     */
    /** Every layout ability Layout Builder checks; bulk_mutate gates bulk layout changes. */
    public const array LAYOUT_ABILITIES = [
        'view_any',
        'view',
        'create',
        'edit_content',
        'edit_layout',
        'update',
        'delete',
        'delete_any',
        'restore',
        'force_delete',
        'replicate',
        'reorder',
        'bulk_mutate',
    ];

    public const array WIDGET_ABILITIES = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'delete_any',
        'restore',
        'restore_any',
        'force_delete',
        'force_delete_any',
        'replicate',
        'reorder',
    ];

    public static function bulkMutateLayoutsPermission(): string
    {
        return self::permission('bulk_mutate', self::SUBJECT);
    }

    /**
     * @return list<string>
     */
    public static function permissionsForRole(string $roleName): array
    {
        return match ($roleName) {
            'editor' => LayoutBuilderPermissionsContribution::defaultsForRole('editor'),
            'admin' => [
                ...array_map(static fn (string $ability): string => self::permission($ability, self::SUBJECT), self::LAYOUT_ABILITIES),
                ...self::widgetPermissions(),
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function widgetPermissions(): array
    {
        return array_map(
            static fn (string $ability): string => self::permission($ability, self::WIDGET_SUBJECT),
            self::WIDGET_ABILITIES,
        );
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

        return array_values(Permission::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->sort()
            ->values()
            ->all());
    }
}
