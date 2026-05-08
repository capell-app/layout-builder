<?php

declare(strict_types=1);

use Capell\Admin\Enums\ExtensionGroupEnum;
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

it('registers developer tools and health pages into mandatory extension groups', function (): void {
    $extensionGroups = collect(resolve(ExtensionPageRegistry::class)->entries())
        ->mapWithKeys(fn (array $extensionPage): array => [$extensionPage['page'] => $extensionPage['extensionGroup']]);

    expect($extensionGroups->get(DiagnosticsPage::class))->toBe(ExtensionGroupEnum::DeveloperTools)
        ->and($extensionGroups->get(QueueHealthPage::class))->toBe(ExtensionGroupEnum::Health)
        ->and($extensionGroups->get(PermissionAuditPage::class))->toBe(ExtensionGroupEnum::Security);
});
