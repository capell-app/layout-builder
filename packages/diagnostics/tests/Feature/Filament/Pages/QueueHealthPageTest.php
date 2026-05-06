<?php

declare(strict_types=1);

use Capell\Diagnostics\Filament\Pages\QueueHealthPage;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.super_admin', 'super_admin'));
    Permission::findOrCreate('View:QueueHealthPage');
    Permission::findOrCreate('accessDiagnostics');
});

it('does not allow generic queue health page permission to view failed job details', function (): void {
    $user = $this->createUserWithPermission('View:QueueHealthPage');

    $this->actingAs($user);

    expect(QueueHealthPage::canAccess())->toBeFalse();
});

it('allows super admins to view queue health', function (): void {
    $user = $this->createUserWithRole(config('capell.roles.super_admin', 'super_admin'));

    $this->actingAs($user);

    expect(QueueHealthPage::canAccess())->toBeTrue();
});

it('allows users with developer tools access to view queue health', function (): void {
    $user = $this->createUserWithPermission('accessDiagnostics');

    $this->actingAs($user);

    expect(QueueHealthPage::canAccess())->toBeTrue();
});
