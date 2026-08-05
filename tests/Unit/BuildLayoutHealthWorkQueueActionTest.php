<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildLayoutHealthWorkQueueAction;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBulkChangeScopedUser;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

it('bounds the layout health work queue before it reaches the dashboard', function (): void {
    test()->actingAsAdmin();

    Widget::factory()->create(['name' => 'C unavailable widget', 'status' => false]);
    Widget::factory()->create(['name' => 'A unavailable widget', 'status' => false]);
    Widget::factory()->create(['name' => 'B unavailable widget', 'status' => false]);

    $items = BuildLayoutHealthWorkQueueAction::run(2);

    expect($items)->toHaveCount(2)
        ->and($items->pluck('kind')->all())->toBe(['unavailable_widget', 'unavailable_widget'])
        ->and($items->pluck('title')->all())->toBe(['A unavailable widget', 'B unavailable widget']);
});

it('scopes layout and page reachability work to the current actor sites', function (): void {
    $allowedSite = Site::factory()->create();
    $hiddenSite = Site::factory()->create();
    $allowedLayout = Layout::factory()->site($allowedSite)->create(['name' => 'Allowed disabled layout', 'status' => false]);
    $hiddenLayout = Layout::factory()->site($hiddenSite)->create(['name' => 'Hidden disabled layout', 'status' => false]);
    Page::factory()->site($allowedSite)->layout($allowedLayout)->create(['name' => 'Allowed published page']);
    Page::factory()->site($hiddenSite)->layout($hiddenLayout)->create(['name' => 'Hidden published page']);

    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    Permission::findOrCreate('ViewAny:Layout', 'web');
    Permission::findOrCreate('ViewAny:Page', 'web');
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Site editor',
        'email' => 'layout-health-site-editor@example.test',
        'password' => 'password',
    ]);
    $actor->givePermissionTo(['ViewAny:Layout', 'ViewAny:Page']);
    $actorId = $actor->getKey();
    $allowedSiteId = $allowedSite->getKey();

    if (! is_int($actorId) || ! is_int($allowedSiteId)) {
        throw new RuntimeException('Expected persisted actor and site IDs.');
    }

    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[$actorId] = [$allowedSiteId];
    test()->actingAs($actor);

    $items = BuildLayoutHealthWorkQueueAction::run(12);

    expect($items->pluck('title')->all())
        ->toContain('Allowed disabled layout', 'Allowed published page')
        ->not->toContain('Hidden disabled layout', 'Hidden published page');
});

it('retains accessible page reachability work after alphabetically earlier restricted pages', function (): void {
    $site = Site::factory()->create();
    $disabledLayout = Layout::factory()->site($site)->create(['status' => false]);
    $restrictedBlueprint = Blueprint::factory()->page()->create();
    $restrictedRole = Role::findOrCreate('layout-health-restricted-page-viewer', 'web');
    $restrictedBlueprint->roleRestrictions()->create(['role_id' => $restrictedRole->getKey()]);

    foreach (range(1, 12) as $number) {
        Page::factory()
            ->site($site)
            ->layout($disabledLayout)
            ->type($restrictedBlueprint)
            ->create(['name' => sprintf('A hidden reachability page %02d', $number)]);
    }

    Page::factory()
        ->site($site)
        ->layout($disabledLayout)
        ->create(['name' => 'Z accessible reachability page']);

    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    Permission::findOrCreate('ViewAny:Page', 'web');
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Reachability editor',
        'email' => 'layout-health-reachability-editor@example.test',
        'password' => 'password',
    ]);
    $actor->givePermissionTo('ViewAny:Page');
    $actorId = $actor->getKey();
    $siteId = $site->getKey();

    if (! is_int($actorId) || ! is_int($siteId)) {
        throw new RuntimeException('Expected persisted actor and site IDs.');
    }

    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[$actorId] = [$siteId];
    test()->actingAs($actor);

    $items = BuildLayoutHealthWorkQueueAction::run(1);

    expect($items->pluck('title')->all())->toBe(['Z accessible reachability page']);
});

it('stops page reachability work after the candidate cap is exhausted', function (): void {
    $site = Site::factory()->create();
    $disabledLayout = Layout::factory()->site($site)->create(['status' => false]);
    $restrictedBlueprint = Blueprint::factory()->page()->create();
    $restrictedRole = Role::findOrCreate('layout-health-large-prefix-viewer', 'web');
    $restrictedBlueprint->roleRestrictions()->create(['role_id' => $restrictedRole->getKey()]);

    foreach (range(1, 48) as $number) {
        Page::factory()
            ->site($site)
            ->layout($disabledLayout)
            ->type($restrictedBlueprint)
            ->create(['name' => sprintf('A restricted reachability page %03d', $number)]);
    }

    Page::factory()
        ->site($site)
        ->layout($disabledLayout)
        ->create(['name' => 'Z accessible reachability page after large prefix']);

    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    Permission::findOrCreate('ViewAny:Page', 'web');
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Large prefix reachability editor',
        'email' => 'layout-health-large-prefix@example.test',
        'password' => 'password',
    ]);
    $actor->givePermissionTo('ViewAny:Page');
    $actorId = $actor->getKey();
    $siteId = $site->getKey();

    if (! is_int($actorId) || ! is_int($siteId)) {
        throw new RuntimeException('Expected persisted actor and site IDs.');
    }

    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[$actorId] = [$siteId];
    test()->actingAs($actor);

    $items = BuildLayoutHealthWorkQueueAction::run(1);

    expect($items)->toBeEmpty();
});

it('bounds page reachability policy queries when every candidate has a distinct blueprint and site key', function (): void {
    $site = Site::factory()->create();
    $disabledLayout = Layout::factory()->site($site)->create(['status' => false]);
    $restrictedRole = Role::findOrCreate('layout-health-varied-key-viewer', 'web');

    foreach (range(1, 96) as $number) {
        $restrictedBlueprint = Blueprint::factory()->page()->create();
        $restrictedBlueprint->roleRestrictions()->create(['role_id' => $restrictedRole->getKey()]);

        Page::factory()
            ->site($site)
            ->layout($disabledLayout)
            ->type($restrictedBlueprint)
            ->create(['name' => sprintf('A varied key restricted reachability page %03d', $number)]);
    }

    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    Permission::findOrCreate('ViewAny:Page', 'web');
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Varied key reachability editor',
        'email' => 'layout-health-varied-key@example.test',
        'password' => 'password',
    ]);
    $actor->givePermissionTo('ViewAny:Page');
    $actorId = $actor->getKey();
    $siteId = $site->getKey();

    if (! is_int($actorId) || ! is_int($siteId)) {
        throw new RuntimeException('Expected persisted actor and site IDs.');
    }

    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[$actorId] = [$siteId];
    test()->actingAs($actor);

    $rolePolicyQueryCount = 0;
    DB::listen(function (QueryExecuted $query) use (&$rolePolicyQueryCount): void {
        if (str_contains($query->sql, 'model_has_roles')) {
            $rolePolicyQueryCount++;
        }
    });

    $items = BuildLayoutHealthWorkQueueAction::run(1);

    // One initial role lookup plus one policy check for each capped candidate.
    expect($items)->toBeEmpty()
        ->and($rolePolicyQueryCount)->toBeLessThanOrEqual(49);
});

it('does not describe widget usage as unused when a global actor cannot view layouts', function (): void {
    Permission::findOrCreate('ViewAny:Layout', 'web');
    $actor = new class extends User
    {
        protected $table = 'users';

        public function isGlobalAdmin(): bool
        {
            return true;
        }

        public function getMorphClass(): string
        {
            return User::class;
        }
    };
    $actor->forceFill([
        'name' => 'Widget-only global actor',
        'email' => 'layout-health-widget-only-global@example.test',
        'password' => 'password',
    ]);
    $actor->save();
    test()->actingAs($actor);

    $widget = Widget::factory()->create([
        'name' => 'Globally visible widget without layout access',
        'status' => true,
    ]);

    expect(WidgetResource::canViewAny())->toBeTrue()
        ->and(LayoutResource::canViewAny())->toBeFalse();

    $item = BuildLayoutHealthWorkQueueAction::run(12)
        ->firstWhere('title', $widget->name);

    expect($item)->not->toBeNull()
        ->and($item?->kind)->toBe('widget_no_tracked_uses')
        ->and($item?->description)->toBe((string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_widget_no_tracked_uses'));
});

it('includes authorized stable resource parameters in work queue links', function (): void {
    test()->actingAsAdmin();

    $widget = Widget::factory()->create([
        'name' => 'Unavailable hero',
        'status' => false,
    ]);

    $item = BuildLayoutHealthWorkQueueAction::run(1)->first();

    expect($item)->not->toBeNull()
        ->and($item?->kind)->toBe('unavailable_widget')
        ->and($item?->url)->toBe(WidgetResource::getUrl('edit', ['record' => $widget]));
});
