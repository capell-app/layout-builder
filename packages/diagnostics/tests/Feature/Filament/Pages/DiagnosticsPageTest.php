<?php

declare(strict_types=1);

use Capell\Admin\Support\Extensions\ExtensionPageRegistry;
use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Capell\Diagnostics\Filament\Pages\PermissionAuditPage;
use Capell\Diagnostics\Filament\Pages\QueueHealthPage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.super_admin', 'super_admin'));
    Permission::findOrCreate('accessDiagnostics');
});

it('documents extension authoring on the developer tools page', function (): void {
    $user = $this->createUserWithPermission('accessDiagnostics');

    $this->actingAs($user);

    Livewire::test(DiagnosticsPage::class)
        ->assertSuccessful()
        ->assertSee(__('capell-diagnostics::package.extension_authoring_heading'))
        ->assertSee('https://docs.capell.app/packages/how-to-create-a-capell-extension');
});

it('allows super admins to access developer tools', function (): void {
    $user = $this->createUserWithRole(config('capell.roles.super_admin', 'super_admin'));

    $this->actingAs($user);

    expect(DiagnosticsPage::canAccess())->toBeTrue();
});

it('registers developer tools and health pages as extension pages', function (): void {
    $extensionPages = collect(resolve(ExtensionPageRegistry::class)->entries())
        ->pluck('page');

    expect($extensionPages)->toContain(DiagnosticsPage::class)
        ->and($extensionPages)->toContain(QueueHealthPage::class)
        ->and($extensionPages)->toContain(PermissionAuditPage::class);
});
