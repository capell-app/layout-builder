<?php

declare(strict_types=1);

use Capell\DeveloperTools\Actions\Reports\BuildPermissionAuditQueryAction;
use Spatie\Permission\Models\Role;

describe('BuildPermissionAuditQueryAction', function (): void {
    it('returns query builder for roles', function (): void {
        // Arrange
        Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        // Act
        $query = BuildPermissionAuditQueryAction::run();
        $roles = $query->get();

        // Assert
        expect($roles)->not->toBeEmpty();
    });

    it('excludes system roles', function (): void {
        // Arrange
        Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        // Act
        $query = BuildPermissionAuditQueryAction::run();
        $roles = $query->get();

        // Assert - should exclude super_admin
        expect($roles->pluck('name'))->not->toContain('super_admin');
    });

    it('excludes the configured super admin role when remapped', function (): void {
        config()->set('capell.roles.super_admin', 'platform-admin');

        Role::query()->firstOrCreate(['name' => 'platform-admin', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $roles = BuildPermissionAuditQueryAction::run()->get();

        expect($roles->pluck('name'))
            ->not->toContain('platform-admin')
            ->toContain('editor');
    });
});
