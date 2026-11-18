<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\Core\Contracts\Extensions\DeclaresPermissions;
use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionPermission;
use Capell\Core\Data\Permissions\PermissionData;
use Capell\Core\Data\Permissions\PermissionSetData;
use Capell\LayoutBuilder\Support\LayoutBuilderPermissionRegistrar;
use Override;

final class LayoutBuilderPermissionsContribution implements DeclaresPermissions, ExtensionContribution, RegistersExtensionPermission
{
    use ResolvesShieldPermission;

    /**
     * Every key Layout Builder checks, in the host's Shield format.
     *
     * @return list<string>
     */
    public static function permissionKeys(): array
    {
        return LayoutBuilderPermissionRegistrar::permissionsForRole('admin');
    }

    /** @return list<string> */
    public static function defaultsForRole(string $role): array
    {
        /** @var array{contributes: list<array{class: string, defaultRoles?: array<string, array<string, list<string>>>}>} $manifest */
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/capell.json'), true, flags: JSON_THROW_ON_ERROR);
        $permissions = [];
        foreach ($manifest['contributes'] as $contribution) {
            if ($contribution['class'] !== self::class) {
                continue;
            }

            foreach ($contribution['defaultRoles'][$role] ?? [] as $subject => $abilities) {
                foreach ($abilities as $ability) {
                    $permissions[] = self::permission($ability, $subject);
                }
            }
        }

        return $permissions;
    }

    #[Override]
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    public function permissions(): PermissionSetData
    {
        $subjects = [
            LayoutBuilderPermissionRegistrar::SUBJECT => LayoutBuilderPermissionRegistrar::LAYOUT_ABILITIES,
            LayoutBuilderPermissionRegistrar::WIDGET_SUBJECT => LayoutBuilderPermissionRegistrar::WIDGET_ABILITIES,
        ];
        $permissions = [];

        foreach ($subjects as $subject => $abilities) {
            foreach ($abilities as $ability) {
                $permissions[] = new PermissionData(
                    key: self::permission($ability, $subject),
                    label: (string) __('capell-layout-builder::permissions.labels.' . $ability, ['subject' => __('capell-layout-builder::permissions.subjects.' . $subject)]),
                    group: (string) __('capell-layout-builder::permissions.subjects.' . $subject),
                );
            }
        }

        return new PermissionSetData($permissions);
    }
}
