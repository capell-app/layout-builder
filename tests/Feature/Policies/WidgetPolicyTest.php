<?php

declare(strict_types=1);

use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Policies\WidgetPolicy;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('registers the package policy explicitly after boot when the host has none', function (): void {
    expect(Gate::policies())->toHaveKey(Widget::class, WidgetPolicy::class)
        ->and(Gate::getPolicyFor(Widget::class))->toBeInstanceOf(WidgetPolicy::class);
});

it('leaves a policy the host registered for the model in place', function (): void {
    Gate::policy(Widget::class, stdClass::class);

    new LayoutBuilderServiceProvider(app())->registerDefaultWidgetPolicy();

    expect(Gate::getPolicyFor(Widget::class))->toBeInstanceOf(stdClass::class);
});

it('allows exactly the ability its own permission grants', function (string $ability, string $permission): void {
    Permission::findOrCreate($permission);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $policy = new WidgetPolicy;
    $widget = Widget::factory()->create();

    $allowed = [
        'viewAny' => $policy->viewAny($user),
        'view' => $policy->view($user),
        'create' => $policy->create($user),
        'update' => $policy->update($user),
        'delete' => $policy->delete($user),
        'deleteAny' => $policy->deleteAny($user),
        'restore' => $policy->restore($user),
        'restoreAny' => $policy->restoreAny($user),
        'forceDelete' => $policy->forceDelete($user),
        'forceDeleteAny' => $policy->forceDeleteAny($user),
        'replicate' => $policy->replicate($user),
        'reorder' => $policy->reorder($user),
    ];

    expect(array_keys(array_filter($allowed)))->toBe([$ability]);
})->with([
    ['viewAny', 'ViewAny:Widget'],
    ['view', 'View:Widget'],
    ['create', 'Create:Widget'],
    ['update', 'Update:Widget'],
    ['delete', 'Delete:Widget'],
    ['deleteAny', 'DeleteAny:Widget'],
    ['restore', 'Restore:Widget'],
    ['restoreAny', 'RestoreAny:Widget'],
    ['forceDelete', 'ForceDelete:Widget'],
    ['forceDeleteAny', 'ForceDeleteAny:Widget'],
    ['replicate', 'Replicate:Widget'],
    ['reorder', 'Reorder:Widget'],
]);

it('denies an authenticated user without the permission', function (): void {
    $policy = new WidgetPolicy;
    $user = User::factory()->create();

    expect($policy->update($user))->toBeFalse()
        ->and($policy->viewAny($user))->toBeFalse();
});

it('grants a global actor every ability without a permission, whatever the host Gate does', function (): void {
    $policy = new WidgetPolicy;
    $widget = Widget::factory()->create();
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('super_admin'));

    expect($policy->update($user))->toBeTrue()
        ->and($policy->forceDelete($user))->toBeTrue()
        ->and($policy->reorder($user))->toBeTrue();
});
