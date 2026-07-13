<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Actions\BulkChanges\PreviewLayoutBulkChangeAction;
use Capell\LayoutBuilder\Actions\BulkChanges\QueueLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Actions\BulkChanges\RevertLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Capell\LayoutBuilder\Jobs\ApplyLayoutBulkChangeRunJob;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\LayoutBuilderPermissionRegistrar;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBulkChangeScopedUser;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

/**
 * @param  array<string, mixed>  $containers
 * @param  array<string, mixed>  $attributes
 */
function bulkLayout(array $containers, array $attributes = []): Layout
{
    return Layout::factory()->create(['status' => true, 'containers' => $containers, ...$attributes]);
}

/**
 * @param  array<string, mixed>  $payload
 */
function bulkCriteria(array $payload = []): LayoutBulkChangeCriteriaData
{
    return LayoutBulkChangeCriteriaData::fromPayload(['active_only' => true, ...$payload]);
}

/**
 * @param  array<string, mixed>  $payload
 */
function bulkWidgetOperation(array $payload): LayoutBulkWidgetOperationData
{
    return LayoutBulkWidgetOperationData::fromPayload($payload);
}

function bulkFreshLayout(Layout $layout): Layout
{
    $freshLayout = $layout->fresh();

    if (! $freshLayout instanceof Layout) {
        throw new RuntimeException('Expected the bulk-change test layout to exist.');
    }

    return $freshLayout;
}

function bulkFreshRun(LayoutBulkChangeRun $run): LayoutBulkChangeRun
{
    $freshRun = $run->fresh();

    if (! $freshRun instanceof LayoutBulkChangeRun) {
        throw new RuntimeException('Expected the bulk-change run to exist.');
    }

    return $freshRun;
}

function bulkFirstResult(LayoutBulkChangeRun $run): LayoutBulkChangeResult
{
    $result = $run->results()->first();

    if (! $result instanceof LayoutBulkChangeResult) {
        throw new RuntimeException('Expected the bulk-change run to have a result.');
    }

    return $result;
}

function bulkFreshResult(LayoutBulkChangeResult $result): LayoutBulkChangeResult
{
    $freshResult = $result->fresh();

    if (! $freshResult instanceof LayoutBulkChangeResult) {
        throw new RuntimeException('Expected the bulk-change result to exist.');
    }

    return $freshResult;
}

/**
 * @return list<array<string, mixed>>
 */
function bulkContainerWidgets(Layout $layout, string $containerKey): array
{
    $containers = bulkFreshLayout($layout)->containers ?? [];
    $container = is_array($containers) ? ($containers[$containerKey] ?? []) : [];
    $widgets = is_array($container) ? ($container['widgets'] ?? []) : [];

    if (! is_array($widgets)) {
        return [];
    }

    $normalizedWidgets = [];

    foreach ($widgets as $widget) {
        if (is_array($widget)) {
            $normalizedWidgets[] = $widget;
        }
    }

    return $normalizedWidgets;
}

/**
 * @return list<string>
 */
function bulkWidgetKeys(Layout $layout, string $containerKey): array
{
    $keys = [];

    foreach (bulkContainerWidgets($layout, $containerKey) as $widget) {
        $key = $widget['widget_key'] ?? null;

        if (is_string($key)) {
            $keys[] = $key;
        }
    }

    return $keys;
}

/**
 * @return list<array<string, mixed>>
 */
function bulkContainerDiffs(LayoutBulkChangeResult $result): array
{
    $changes = $result->changes ?? [];
    $diffs = $changes['container_diffs'] ?? [];

    if (! is_array($diffs)) {
        return [];
    }

    $normalizedDiffs = [];

    foreach ($diffs as $diff) {
        if (is_array($diff)) {
            $normalizedDiffs[] = $diff;
        }
    }

    return $normalizedDiffs;
}

/**
 * @return list<string>
 */
function bulkResultWarnings(LayoutBulkChangeResult $result): array
{
    $warnings = $result->warnings ?? [];

    if (! is_array($warnings)) {
        return [];
    }

    $normalizedWarnings = [];

    foreach ($warnings as $warning) {
        if (is_string($warning)) {
            $normalizedWarnings[] = $warning;
        }
    }

    return $normalizedWarnings;
}

it('creates a persisted preview without mutating layouts and records page counts', function (): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    Page::factory()->count(2)->create(['layout_id' => $layout->id]);

    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['require_widget_key' => 'breadcrumbs']), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));

    expect(bulkWidgetKeys($layout, 'main')[0] ?? null)->toBe('breadcrumbs')
        ->and($run->status)->toBe(LayoutBulkChangeRunStatus::Previewed)
        ->and($run->summary)->toMatchArray(['target_layouts' => 1, 'target_pages' => 2, 'changed_layouts' => 1])
        ->and(bulkFirstResult($run)->page_count)->toBe(2)
        ->and(bulkFirstResult($run)->status)->toBe(LayoutBulkChangeResultStatus::Changed)
        ->and(bulkContainerDiffs(bulkFirstResult($run))[0] ?? [])->toMatchArray([
            'container' => 'main',
            'before' => ['breadcrumbs#1', 'hero#1'],
            'after' => ['hero#1', 'breadcrumbs#1'],
        ]);
});

it('applies only changed preview results and leaves skipped layouts untouched', function (): void {
    $changedLayout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $skippedLayout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'content', 'container' => 'main', 'occurrence' => 1]]]]);

    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['require_widget_key' => 'breadcrumbs']), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));

    $summary = ApplyLayoutBulkChangeRunAction::run($run);

    expect($summary['applied_layouts'])->toBe(1)
        ->and(bulkWidgetKeys($changedLayout, 'main'))->toBe(['hero', 'breadcrumbs'])
        ->and(bulkWidgetKeys($skippedLayout, 'main'))->toBe(['breadcrumbs', 'content']);
});

it('reverts an applied run when the layout has not drifted', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $page = Page::factory()->create(['layout_id' => $layout->id]);
    $asset = WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));
    ApplyLayoutBulkChangeRunAction::run($run);

    $summary = RevertLayoutBulkChangeRunAction::run(bulkFreshRun($run));

    expect($summary['reverted_layouts'])->toBe(1)
        ->and(bulkWidgetKeys($layout, 'main'))->toBe(['breadcrumbs', 'hero'])
        ->and($asset->fresh()->container)->toBe('main')
        ->and(bulkFreshRun($run)->status)->toBe(LayoutBulkChangeRunStatus::Reverted);
});

it('skips drifted layouts on approval', function (): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));
    $layout->update(['containers' => ['main' => ['widgets' => [['widget_key' => 'content', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]]);

    $summary = ApplyLayoutBulkChangeRunAction::run($run);

    expect($summary['applied_layouts'])->toBe(0)
        ->and($summary['drifted_layouts'])->toBe(1)
        ->and(bulkFreshResult(bulkFirstResult($run))->status)->toBe(LayoutBulkChangeResultStatus::Drifted);
});

it('migrates page-scoped widget assets when moved widgets change occurrence', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]], 'sidebar' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 1]]]]);
    $page = Page::factory()->create(['layout_id' => $layout->id]);
    $asset = WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'target_container_key' => 'sidebar',
        'placement' => 'bottom',
        'occurrence_mode' => 'first',
    ]));

    ApplyLayoutBulkChangeRunAction::run($run);

    expect($asset->fresh()->container)->toBe('sidebar')
        ->and($asset->fresh()->occurrence)->toBe(2);
});

it('blocks approval when default widget assets would become ambiguous', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]], 'sidebar' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 1]]]]);
    WidgetAsset::factory()->widget($breadcrumbs)->container('main')->occurrence(1)->create(['pageable_type' => null, 'pageable_id' => null]);
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'target_container_key' => 'sidebar',
        'placement' => 'bottom',
        'occurrence_mode' => 'first',
    ]));

    expect($run->status)->toBe(LayoutBulkChangeRunStatus::Blocked)
        ->and(fn (): mixed => ApplyLayoutBulkChangeRunAction::run($run))->toThrow(LogicException::class);
});

it('warns about removed page-scoped assets unless auto delete is selected', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $page = Page::factory()->create(['layout_id' => $layout->id]);
    $asset = WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();

    $warningRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'warn',
    ]));

    expect($warningRun->status)->toBe(LayoutBulkChangeRunStatus::Previewed)
        ->and(bulkResultWarnings(bulkFirstResult($warningRun))[0] ?? null)->toContain('page-scoped widget asset');

    $deleteRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));

    ApplyLayoutBulkChangeRunAction::run($deleteRun);

    expect(WidgetAsset::query()->whereKey($asset->getKey())->exists())->toBeFalse();
});

it('queues a preview run for asynchronous apply', function (): void {
    Queue::fake();
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));
    $user = User::factory()->createOne();

    $queuedRun = QueueLayoutBulkChangeRunAction::run($run, (int) $user->getKey());

    expect($queuedRun->status)->toBe(LayoutBulkChangeRunStatus::Queued)
        ->and($queuedRun->queued_by)->toBe($user->getKey());

    Queue::assertPushed(ApplyLayoutBulkChangeRunJob::class);
});

it('does not apply a queued bulk change after its actor is deleted or de-authorized', function (bool $deleteActor): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));
    Permission::findOrCreate(LayoutBuilderPermissionRegistrar::bulkMutateLayoutsPermission(), 'web');
    $actor = User::factory()->createOne();
    $actor->givePermissionTo(LayoutBuilderPermissionRegistrar::bulkMutateLayoutsPermission());
    $queuedRun = QueueLayoutBulkChangeRunAction::run($run, bulkLayoutTestInteger($actor->getKey()));

    if ($deleteActor) {
        $actor->delete();
    } else {
        $actor->revokePermissionTo(LayoutBuilderPermissionRegistrar::bulkMutateLayoutsPermission());
    }

    (new ApplyLayoutBulkChangeRunJob(bulkLayoutTestInteger($queuedRun->getKey()), bulkLayoutTestInteger($actor->getKey())))->handle();

    expect(bulkFreshRun($queuedRun)->status)->toBe(LayoutBulkChangeRunStatus::Failed)
        ->and(bulkFreshRun($queuedRun)->summary)->toMatchArray([
            'error' => __('capell-layout-builder::message.bulk_change_actor_unauthorized'),
        ])
        ->and(bulkWidgetKeys($layout, 'main'))->toBe(['breadcrumbs', 'hero']);
})->with([
    'deleted actor' => true,
    'revoked actor permission' => false,
]);

it('scopes previews and revalidates mutations to the queued actor sites', function (): void {
    $allowedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $allowedLayout = bulkLayout([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
        ]],
    ], ['site_id' => $allowedSite->getKey()]);
    $otherLayout = bulkLayout([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
        ]],
    ], ['site_id' => $otherSite->getKey()]);
    config()->set('auth.providers.users.model', LayoutBulkChangeScopedUser::class);
    $actor = LayoutBulkChangeScopedUser::query()->create([
        'name' => 'Layout site editor',
        'email' => 'layout-site-editor@example.test',
        'password' => 'password',
    ]);
    LayoutBulkChangeScopedUser::$assignedSiteIdsByUser[bulkLayoutTestInteger($actor->getKey())] = [bulkLayoutTestInteger($allowedSite->getKey())];
    $operation = bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]);

    $scopedRun = PreviewLayoutBulkChangeAction::run(bulkCriteria([
        'layout_keys' => [$allowedLayout->key, $otherLayout->key],
    ]), $operation, bulkLayoutTestInteger($actor->getKey()));

    expect($scopedRun->results()->pluck('layout_id')->all())->toBe([$allowedLayout->getKey()]);

    $unscopedRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$otherLayout->key]]), $operation);
    $summary = ApplyLayoutBulkChangeRunAction::run($unscopedRun, bulkLayoutTestInteger($actor->getKey()));

    expect($summary)->toMatchArray(['applied_layouts' => 0, 'apply_skipped_layouts' => 1])
        ->and(bulkWidgetKeys($otherLayout, 'main'))->toBe(['breadcrumbs', 'hero'])
        ->and(bulkFreshRun($unscopedRun)->status)->toBe(LayoutBulkChangeRunStatus::PartiallyApplied);
});

it('applies a redelivered queued bulk change only once', function (): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));

    $queuedRun = QueueLayoutBulkChangeRunAction::run($run);
    $firstDelivery = new ApplyLayoutBulkChangeRunJob(bulkLayoutTestInteger($queuedRun->getKey()));
    $secondDelivery = new ApplyLayoutBulkChangeRunJob(bulkLayoutTestInteger($queuedRun->getKey()));

    $firstDelivery->handle();
    $secondDelivery->handle();

    expect(bulkFreshRun($queuedRun)->status)->toBe(LayoutBulkChangeRunStatus::Applied)
        ->and(bulkFreshRun($queuedRun)->summary)->toMatchArray(['applied_layouts' => 1])
        ->and(bulkWidgetKeys($layout, 'main'))->toBe(['hero', 'breadcrumbs']);
});

it('previews and approves bulk changes through the artisan command with json output', function (): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $specPath = sys_get_temp_dir() . '/testing-layout-bulk-change.json';
    File::put($specPath, json_encode(['criteria' => ['layout_keys' => [$layout->key]], 'operation' => ['type' => LayoutBulkWidgetOperationType::MoveWidget->value, 'source_widget_key' => 'breadcrumbs', 'target_widget_key' => 'hero', 'placement' => 'after']], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertSuccessful();
    $run = LayoutBulkChangeRun::query()->latest('id')->firstOrFail();
    $this->artisan('capell:layouts:bulk-change', ['--approve' => $run->uuid, '--json' => true])->assertSuccessful();

    expect(bulkWidgetKeys($layout, 'main'))->toBe(['hero', 'breadcrumbs']);
});

it('rejects invalid artisan specs', function (): void {
    $specPath = sys_get_temp_dir() . '/testing-layout-bulk-change-invalid.json';
    File::put($specPath, json_encode(['criteria' => []], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertFailed();
});

it('rejects specs missing required targets for operation type', function (): void {
    $specPath = sys_get_temp_dir() . '/testing-layout-bulk-change-missing-target.json';
    File::put($specPath, json_encode(['criteria' => [], 'operation' => ['type' => LayoutBulkWidgetOperationType::MoveWidget->value, 'source_widget_key' => 'breadcrumbs']], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertFailed();
});

function bulkLayoutTestInteger(mixed $value): int
{
    if (! is_numeric($value)) {
        throw new RuntimeException('Expected a numeric bulk layout test value.');
    }

    return (int) $value;
}
