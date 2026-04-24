<?php

declare(strict_types=1);

use Capell\Admin\Settings\AdminSettings;
use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Filament\Widgets\WorkspaceActivityWidgetAbstract as WorkspaceActivityWidget;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.admin', 'admin'));
    Role::findOrCreate(config('capell.roles.developer', 'developer'));
});

it('is hidden when unauthenticated', function (): void {
    expect(WorkspaceActivityWidget::canView())->toBeFalse();
});

it('is hidden for users without admin or developer role', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(WorkspaceActivityWidget::canView())->toBeFalse();
});

it('is visible for admin role', function (): void {
    $user = User::factory()->create();
    $user->assignRole(config('capell.roles.admin', 'admin'));
    $this->actingAs($user);

    expect(WorkspaceActivityWidget::canView())->toBeTrue();
});

it('is hidden when settings key is disabled', function (): void {
    $user = User::factory()->create();
    $user->assignRole(config('capell.roles.admin', 'admin'));
    $this->actingAs($user);

    $settings = resolve(AdminSettings::class);
    $settings->enabled_widgets = ['workspace_activity' => false];
    $settings->save();

    expect(WorkspaceActivityWidget::canView())->toBeFalse();
});

it('renders without errors for admin role', function (): void {
    $user = User::factory()->create();
    $user->assignRole(config('capell.roles.admin', 'admin'));
    $this->actingAs($user);

    livewire(WorkspaceActivityWidget::class)->assertOk();
});
