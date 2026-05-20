<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::findOrCreate('EditLayout:Layout');
    Role::findOrCreate('layout-manager')->givePermissionTo('EditLayout:Layout');
    Role::findOrCreate('global-viewer');
});

it('denies preset management to authenticated users without layout permission', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();

    expect($user->can('create', [LayoutPreset::class, $site]))->toBeFalse();
});

it('allows preset management for users with layout permission on the preset site only', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $user = User::factory()->create();
    $role = Role::findOrCreate('layout-manager');
    $preset = LayoutPreset::factory()->for($assignedSite, 'site')->create();

    DB::table('model_has_roles')->insert([
        'role_id' => $role->getKey(),
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'team_id' => $assignedSite->getKey(),
    ]);

    expect($user->can('create', [LayoutPreset::class, $assignedSite]))->toBeTrue()
        ->and($user->can('apply', [$preset, $assignedSite]))->toBeTrue()
        ->and($user->can('apply', [$preset, $otherSite]))->toBeFalse()
        ->and($user->can('create', [LayoutPreset::class, $otherSite]))->toBeFalse();
});

it('does not allow arbitrary global roles to manage layout presets', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();

    $user->assignRole('global-viewer');

    expect($user->can('create', [LayoutPreset::class, $site]))->toBeFalse();
});

it('allows globally permitted users to manage layout presets without a site role', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();
    $preset = LayoutPreset::factory()->for($site, 'site')->create();

    $user->givePermissionTo('EditLayout:Layout');

    expect($user->can('create', [LayoutPreset::class, $site]))->toBeTrue()
        ->and($user->can('apply', [$preset, $site]))->toBeTrue();
});

it('enforces the preset create policy in the layout builder component', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();
    test()->actingAs($user);

    $component = new LayoutBuilder;
    $assertCanCreatePreset = Closure::bind(
        fn (Site $site): mixed => $this->assertCanCreateLayoutPreset($site),
        $component,
        LayoutBuilder::class,
    );

    $assertCanCreatePreset($site);
})->throws(AuthorizationException::class);

it('enforces the preset apply policy in the layout builder component', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();
    $preset = LayoutPreset::factory()->for($site, 'site')->create();
    test()->actingAs($user);

    $component = new LayoutBuilder;
    $assertCanApplyPreset = Closure::bind(
        fn (LayoutPreset $preset, Site $site): mixed => $this->assertCanApplyLayoutPreset($preset, $site),
        $component,
        LayoutBuilder::class,
    );

    $assertCanApplyPreset($preset, $site);
})->throws(AuthorizationException::class);
