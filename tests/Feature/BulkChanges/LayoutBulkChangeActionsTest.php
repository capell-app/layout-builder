<?php

declare(strict_types=1);

use Capell\Core\Data\PageVariationData;
use Capell\Core\Facades\CapellCore;
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
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderDemoContentPage;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBulkChangeScopedUser;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\DB;
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
            $normalizedWidgets[] = capell_string_keyed_array($widget);
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
            $normalizedDiffs[] = capell_string_keyed_array($diff);
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

it('moves page-scoped assets in bounded batches on apply and revert', function (string $phase): void {
    config()->set('capell-layout-builder.bulk_change_asset_delete_chunk_size', 4);
    CapellCore::registerPageVariation(new PageVariationData('bulk-change-demo-page', LayoutBuilderDemoContentPage::class));
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $containers = [
        'main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]],
        'sidebar' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 1]]],
    ];
    $layout = bulkLayout($containers, ['site_id' => Site::factory()->create()->getKey()]);
    $otherLayout = bulkLayout($containers);
    $pages = Page::factory()->count(17)->create(['layout_id' => $layout->getKey(), 'site_id' => $layout->site_id]);
    $page = $pages->firstOrFail();
    $otherPage = Page::factory()->create(['layout_id' => $otherLayout->getKey()]);
    $pageTypes = [$page->getMorphClass(), (new LayoutBuilderDemoContentPage)->getMorphClass()];

    foreach ($pages as $scopedPage) {
        foreach ($pageTypes as $pageType) {
            WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create([
                'pageable_type' => $pageType,
                'pageable_id' => $scopedPage->getKey(),
            ]);
        }
    }

    $retainedAssets = [
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($otherPage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(3)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'sidebar', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 3)->create(),
    ];
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'target_container_key' => 'sidebar',
        'placement' => 'bottom',
        'occurrence_mode' => 'first',
    ]));

    if ($phase === 'revert') {
        ApplyLayoutBulkChangeRunAction::run($run);
    }

    $connection = DB::connection();
    $wasLoggingQueries = $connection->logging();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $summary = $phase === 'apply'
            ? ApplyLayoutBulkChangeRunAction::run($run)
            : RevertLayoutBulkChangeRunAction::run(bulkFreshRun($run));
    } finally {
        $queries = $connection->getQueryLog();
        $connection->flushQueryLog();

        if (! $wasLoggingQueries) {
            $connection->disableQueryLog();
        }
    }

    $updateQueries = array_filter(
        $queries,
        static fn (array $query): bool => str_starts_with(strtolower(ltrim($query['query'])), 'update ')
            && str_contains($query['query'], 'widget_assets'),
    );
    $movedAssets = WidgetAsset::query()->where('widget_id', $breadcrumbs->getKey())
        ->where('container', $phase === 'apply' ? 'sidebar' : 'main')
        ->where('occurrence', $phase === 'apply' ? 2 : 1)
        ->whereIn('pageable_type', $pageTypes)
        ->whereIn('pageable_id', $pages->modelKeys())
        ->count();

    expect($summary[$phase === 'apply' ? 'applied_layouts' : 'reverted_layouts'])->toBe(1)
        ->and($movedAssets)->toBe(34)
        ->and($updateQueries)->toHaveCount(10);

    foreach ($updateQueries as $query) {
        expect(count($query['bindings']))->toBeLessThanOrEqual(11);
    }

    foreach ($retainedAssets as $asset) {
        expect($asset->fresh()->container)->toBe($asset->container)
            ->and($asset->fresh()->occurrence)->toBe($asset->occurrence);
    }
})->with(['apply', 'revert']);

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

it('deletes removed widget assets only for pages using the selected layout', function (): void {
    CapellCore::registerPageVariation(new PageVariationData('bulk-change-demo-page', LayoutBuilderDemoContentPage::class));
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $hero = Widget::factory()->create(['key' => 'hero']);
    $containers = [
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 2],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
        ]],
        'sidebar' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 3],
        ]],
    ];
    $layout = bulkLayout($containers, ['site_id' => $site->getKey()]);
    $otherLayout = bulkLayout($containers, ['site_id' => $site->getKey()]);
    $otherSiteLayout = bulkLayout($containers, ['site_id' => $otherSite->getKey()]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey(), 'site_id' => $site->getKey()]);
    $secondPage = Page::factory()->create(['layout_id' => $layout->getKey(), 'site_id' => $site->getKey()]);
    $otherPage = Page::factory()->create(['layout_id' => $otherLayout->getKey(), 'site_id' => $site->getKey()]);
    $otherSitePage = Page::factory()->create(['layout_id' => $otherSiteLayout->getKey(), 'site_id' => $otherSite->getKey()]);
    $pageVariation = LayoutBuilderDemoContentPage::query()->whereKey($page->getKey())->firstOrFail();
    $otherPageVariation = LayoutBuilderDemoContentPage::query()->whereKey($otherPage->getKey())->firstOrFail();
    $removedAssets = [
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($secondPage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create([
            'pageable_type' => $pageVariation->getMorphClass(),
            'pageable_id' => $pageVariation->getKey(),
        ]),
    ];
    $retainedAssets = [
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($otherPage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($otherSitePage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create([
            'pageable_type' => $otherPageVariation->getMorphClass(),
            'pageable_id' => $otherPageVariation->getKey(),
        ]),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'sidebar', 3)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 2)->create(),
        WidgetAsset::factory()->widget($hero)->asset($page)->page($page, 'main', 1)->create(),
    ];
    $warningRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'occurrence_mode' => 'specific',
        'source_occurrence_number' => 1,
        'remove_widget_asset_mode' => 'warn',
    ]));

    expect(bulkResultWarnings(bulkFirstResult($warningRun)))->toBe([
        'Removing this widget will leave 3 page-scoped widget assets unused. Select auto-delete page-scoped assets to remove them during apply.',
    ]);

    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'occurrence_mode' => 'specific',
        'source_occurrence_number' => 1,
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));

    expect($run->results()->pluck('layout_id')->all())->toBe([$layout->getKey()]);

    $summary = ApplyLayoutBulkChangeRunAction::run($run);

    expect($summary['applied_layouts'])->toBe(1)
        ->and(bulkWidgetKeys($layout, 'main'))->toBe(['breadcrumbs', 'hero'])
        ->and(bulkWidgetKeys($otherLayout, 'main'))->toBe(['breadcrumbs', 'breadcrumbs', 'hero'])
        ->and(bulkWidgetKeys($otherSiteLayout, 'main'))->toBe(['breadcrumbs', 'breadcrumbs', 'hero']);

    foreach ($removedAssets as $asset) {
        $this->assertModelMissing($asset);
    }

    foreach ($retainedAssets as $asset) {
        $this->assertModelExists($asset);
    }
});

it('counts removed page-scoped assets separately for each selected layout', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $containers = ['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]]];
    $firstLayout = bulkLayout($containers);
    $secondLayout = bulkLayout($containers);
    $otherLayout = bulkLayout($containers);
    $firstPage = Page::factory()->create(['layout_id' => $firstLayout->getKey()]);
    $secondPages = Page::factory()->count(2)->create(['layout_id' => $secondLayout->getKey()]);
    $otherPage = Page::factory()->create(['layout_id' => $otherLayout->getKey()]);
    $assets = [
        WidgetAsset::factory()->widget($breadcrumbs)->asset($firstPage)->page($firstPage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($otherPage)->page($otherPage, 'main', 1)->create(),
    ];

    foreach ($secondPages as $page) {
        $assets[] = WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();
    }

    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$firstLayout->key, $secondLayout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'warn',
    ]));
    $results = $run->results()->get();
    $firstResult = $results->firstOrFail(static fn (LayoutBulkChangeResult $result): bool => $result->layout_id === $firstLayout->getKey());
    $secondResult = $results->firstOrFail(static fn (LayoutBulkChangeResult $result): bool => $result->layout_id === $secondLayout->getKey());

    expect($results)->toHaveCount(2)
        ->and(bulkResultWarnings($firstResult))->toBe([
            'Removing this widget will leave 1 page-scoped widget asset unused. Select auto-delete page-scoped assets to remove them during apply.',
        ])
        ->and(bulkResultWarnings($secondResult))->toBe([
            'Removing this widget will leave 2 page-scoped widget assets unused. Select auto-delete page-scoped assets to remove them during apply.',
        ]);

    foreach ($assets as $asset) {
        $this->assertModelExists($asset);
    }
});

it('deletes page-scoped assets in bounded batches for each page variation', function (): void {
    config()->set('capell-layout-builder.bulk_change_asset_delete_chunk_size', 2);
    CapellCore::registerPageVariation(new PageVariationData('bulk-change-demo-page', LayoutBuilderDemoContentPage::class));
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $hero = Widget::factory()->create(['key' => 'hero']);
    $containers = ['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]]];
    $layout = bulkLayout($containers);
    $otherLayout = bulkLayout($containers);
    $pages = Page::factory()->count(5)->create(['layout_id' => $layout->getKey()]);
    $page = $pages->firstOrFail();
    $otherPage = Page::factory()->create(['layout_id' => $otherLayout->getKey()]);

    foreach ($pages as $scopedPage) {
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($scopedPage, 'main', 1)->create();
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create([
            'pageable_type' => (new LayoutBuilderDemoContentPage)->getMorphClass(),
            'pageable_id' => $scopedPage->getKey(),
        ]);
    }

    $retainedAssets = [
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($otherPage, 'main', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create([
            'pageable_type' => (new LayoutBuilderDemoContentPage)->getMorphClass(),
            'pageable_id' => $otherPage->getKey(),
        ]),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->container('main')->occurrence(1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'sidebar', 1)->create(),
        WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 2)->create(),
        WidgetAsset::factory()->widget($hero)->asset($page)->page($page, 'main', 1)->create(),
    ];
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));
    $connection = DB::connection();
    $wasLoggingQueries = $connection->logging();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $summary = ApplyLayoutBulkChangeRunAction::run($run);
    } finally {
        $queries = $connection->getQueryLog();
        $connection->flushQueryLog();

        if (! $wasLoggingQueries) {
            $connection->disableQueryLog();
        }
    }

    $deleteQueries = array_filter(
        $queries,
        static fn (array $query): bool => str_starts_with(strtolower(ltrim($query['query'])), 'delete ')
            && str_contains($query['query'], 'widget_assets'),
    );
    $bindingCounts = array_map(static fn (array $query): int => count($query['bindings']), $deleteQueries);
    sort($bindingCounts);
    $remainingAssetCount = WidgetAsset::query()
        ->where('widget_id', $breadcrumbs->getKey())
        ->where('container', 'main')
        ->where('occurrence', 1)
        ->whereIn('pageable_type', [$page->getMorphClass(), (new LayoutBuilderDemoContentPage)->getMorphClass()])
        ->whereIn('pageable_id', Page::query()->where('layout_id', $layout->getKey())->select('id'))
        ->count();

    expect($summary['applied_layouts'])->toBe(1)
        ->and($deleteQueries)->toHaveCount(6)
        ->and($bindingCounts)->toBe([5, 5, 6, 6, 6, 6])
        ->and($remainingAssetCount)->toBe(0)
        ->and(bulkWidgetKeys($layout, 'main'))->toBe([])
        ->and(bulkWidgetKeys($otherLayout, 'main'))->toBe(['breadcrumbs']);

    foreach ($deleteQueries as $query) {
        expect(count($query['bindings']))->toBeLessThanOrEqual(6);
    }

    foreach ($retainedAssets as $asset) {
        $this->assertModelExists($asset);
    }
});

it('clamps oversized page-scoped asset deletion batches', function (): void {
    config()->set('capell-layout-builder.bulk_change_asset_delete_chunk_size', 1000000);
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]]], ['site_id' => Site::factory()->create()->getKey()]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey(), 'site_id' => $layout->site_id]);
    $pages = Page::factory()->count(1000)->make([
        'layout_id' => $layout->getKey(),
        'site_id' => $page->site_id,
        'blueprint_id' => $page->blueprint_id,
    ]);

    foreach ($pages->chunk(50) as $pageChunk) {
        Page::query()->insert($pageChunk->map(static fn (Page $page): array => $page->getAttributes())->all());
    }

    $lastPage = Page::query()->where('layout_id', $layout->getKey())->orderByDesc('id')->firstOrFail();
    WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();
    WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($lastPage, 'main', 1)->create();
    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));
    $connection = DB::connection();
    $wasLoggingQueries = $connection->logging();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        ApplyLayoutBulkChangeRunAction::run($run);
    } finally {
        $queries = $connection->getQueryLog();
        $connection->flushQueryLog();

        if (! $wasLoggingQueries) {
            $connection->disableQueryLog();
        }
    }

    $deleteQueries = array_filter(
        $queries,
        static fn (array $query): bool => str_starts_with(strtolower(ltrim($query['query'])), 'delete ')
            && str_contains($query['query'], 'widget_assets'),
    );

    expect(WidgetAsset::query()->where('widget_id', $breadcrumbs->getKey())->count())->toBe(0)
        ->and($deleteQueries)->toHaveCount(2);

    foreach ($deleteQueries as $query) {
        expect(count($query['bindings']))->toBeLessThanOrEqual(1004);
    }
});

it('counts duplicate removal tuples once so preview matches deleted asset rows', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $layout = bulkLayout(['main' => ['widgets' => [
        ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
        ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
        ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 2],
    ]]]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey()]);
    $otherAssetPage = Page::factory()->create(['site_id' => $page->site_id]);
    WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 1)->create();
    WidgetAsset::factory()->widget($breadcrumbs)->asset($otherAssetPage)->page($page, 'main', 1)->create();
    WidgetAsset::factory()->widget($breadcrumbs)->asset($page)->page($page, 'main', 2)->create();
    $payload = [
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
    ];
    $warningRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        ...$payload,
        'remove_widget_asset_mode' => 'warn',
    ]));
    $deleteRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        ...$payload,
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));
    $before = WidgetAsset::query()->count();

    ApplyLayoutBulkChangeRunAction::run($deleteRun);

    $deleted = $before - WidgetAsset::query()->count();

    expect($deleted)->toBe(3)
        ->and(bulkResultWarnings(bulkFirstResult($warningRun)))->toBe([
            sprintf('Removing this widget will leave %d page-scoped widget assets unused. Select auto-delete page-scoped assets to remove them during apply.', $deleted),
        ]);
});

it('preserves foreign page assets when the selected layout has no pages', function (): void {
    $breadcrumbs = Widget::factory()->create(['key' => 'breadcrumbs']);
    $containers = ['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1]]]];
    $layout = bulkLayout($containers);
    $otherLayout = bulkLayout($containers);
    $otherPage = Page::factory()->create(['layout_id' => $otherLayout->getKey()]);
    $asset = WidgetAsset::factory()->widget($breadcrumbs)->asset($otherPage)->page($otherPage, 'main', 1)->create();
    $warningRun = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'warn',
    ]));

    expect(bulkResultWarnings(bulkFirstResult($warningRun)))->toBe([]);

    $run = PreviewLayoutBulkChangeAction::run(bulkCriteria(['layout_keys' => [$layout->key]]), bulkWidgetOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'remove_widget_asset_mode' => 'delete_page_scoped',
    ]));

    expect(bulkFirstResult($run)->page_count)->toBe(0);

    $summary = ApplyLayoutBulkChangeRunAction::run($run);

    expect($summary['applied_layouts'])->toBe(1)
        ->and(bulkWidgetKeys($layout, 'main'))->toBe([])
        ->and(bulkWidgetKeys($otherLayout, 'main'))->toBe(['breadcrumbs']);
    $this->assertModelExists($asset);
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

    new ApplyLayoutBulkChangeRunJob(bulkLayoutTestInteger($queuedRun->getKey()), bulkLayoutTestInteger($actor->getKey()))->handle();

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
    $specPath = storage_path('app/testing-layout-bulk-change.json');
    File::put($specPath, json_encode(['criteria' => ['layout_keys' => [$layout->key]], 'operation' => ['type' => LayoutBulkWidgetOperationType::MoveWidget->value, 'source_widget_key' => 'breadcrumbs', 'target_widget_key' => 'hero', 'placement' => 'after']], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertSuccessful();
    $run = LayoutBulkChangeRun::query()->latest('id')->firstOrFail();
    $this->artisan('capell:layouts:bulk-change', ['--approve' => $run->uuid, '--json' => true])->assertSuccessful();

    expect(bulkWidgetKeys($layout, 'main'))->toBe(['hero', 'breadcrumbs']);
});

it('rejects invalid artisan specs', function (): void {
    $specPath = storage_path('app/testing-layout-bulk-change-invalid.json');
    File::put($specPath, json_encode(['criteria' => []], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertFailed();
});

it('rejects specs missing required targets for operation type', function (): void {
    $specPath = storage_path('app/testing-layout-bulk-change-missing-target.json');
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
