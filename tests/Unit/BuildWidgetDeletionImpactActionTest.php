<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildWidgetDeletionImpactAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBulkChangeScopedUser;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('builds widget deletion impact from the authoritative layout usage projection', function (): void {
    test()->actingAsAdmin();

    $usedWidget = Widget::factory()->create(['key' => 'used-widget', 'status' => true]);
    $unusedWidget = Widget::factory()->create(['key' => 'unused-widget', 'status' => true]);

    Layout::factory()->count(2)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $usedWidget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $usedImpact = BuildWidgetDeletionImpactAction::run($usedWidget);
    $unusedImpact = BuildWidgetDeletionImpactAction::run($unusedWidget);
    $actionSource = file_get_contents(__DIR__ . '/../../src/Actions/BuildWidgetDeletionImpactAction.php');

    expect($usedImpact->layouts)->toBe(2)
        ->and($unusedImpact->layouts)->toBe(0)
        ->and($usedImpact->isComplete)->toBeTrue()
        ->and($usedImpact->isAuthoritative)->toBeTrue()
        ->and($actionSource)->toContain('withLayoutsCount(LayoutResource::getEloquentQuery())')
        ->and($actionSource)->not->toContain('json_decode');
});

it('scopes widget layout usage to an actor sites without treating a scoped zero as unused', function (): void {
    $allowedSite = Site::factory()->create();
    $hiddenSite = Site::factory()->create();
    $widget = Widget::factory()->create(['key' => 'site-scoped-widget', 'status' => true]);

    Layout::factory()->create([
        'site_id' => $hiddenSite->getKey(),
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    Permission::findOrCreate('ViewAny:Layout', 'web');
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Site editor',
        'email' => 'site-editor@example.test',
        'password' => 'password',
    ]);
    $actor->givePermissionTo('ViewAny:Layout');
    $actorId = $actor->getKey();
    $allowedSiteId = $allowedSite->getKey();

    if (! is_int($actorId) || ! is_int($allowedSiteId)) {
        throw new RuntimeException('Expected persisted actor and site IDs.');
    }

    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[$actorId] = [$allowedSiteId];
    test()->actingAs($actor);

    $impact = BuildWidgetDeletionImpactAction::run($widget);

    expect($impact->layouts)->toBe(0)
        ->and($impact->isComplete)->toBeTrue()
        ->and($impact->isAuthoritative)->toBeFalse();
});
