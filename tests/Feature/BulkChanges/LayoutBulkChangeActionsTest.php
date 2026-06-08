<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
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
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

function bulkLayout(array $containers, array $attributes = []): Layout
{
    return Layout::factory()->create(['status' => true, 'containers' => $containers, ...$attributes]);
}

function bulkCriteria(array $payload = []): LayoutBulkChangeCriteriaData
{
    return LayoutBulkChangeCriteriaData::fromPayload(['active_only' => true, ...$payload]);
}

function bulkWidgetOperation(array $payload): LayoutBulkWidgetOperationData
{
    return LayoutBulkWidgetOperationData::fromPayload($payload);
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

    $result = $run->results()->first();

    expect($layout->fresh()->containers['main']['widgets'][0]['widget_key'])->toBe('breadcrumbs')
        ->and($run->status)->toBe(LayoutBulkChangeRunStatus::Previewed)
        ->and($run->summary)->toMatchArray(['target_layouts' => 1, 'target_pages' => 2, 'changed_layouts' => 1])
        ->and($result->page_count)->toBe(2)
        ->and($result->status)->toBe(LayoutBulkChangeResultStatus::Changed)
        ->and($result->changes['container_diffs'][0])->toMatchArray([
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
        ->and(array_column($changedLayout->fresh()->containers['main']['widgets'], 'widget_key'))->toBe(['hero', 'breadcrumbs'])
        ->and(array_column($skippedLayout->fresh()->containers['main']['widgets'], 'widget_key'))->toBe(['breadcrumbs', 'content']);
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

    $summary = RevertLayoutBulkChangeRunAction::run($run->fresh());

    expect($summary['reverted_layouts'])->toBe(1)
        ->and(array_column($layout->fresh()->containers['main']['widgets'], 'widget_key'))->toBe(['breadcrumbs', 'hero'])
        ->and($asset->fresh()->container)->toBe('main')
        ->and($run->fresh()->status)->toBe(LayoutBulkChangeRunStatus::Reverted);
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
        ->and($run->results()->first()->fresh()->status)->toBe(LayoutBulkChangeResultStatus::Drifted);
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
        ->and($warningRun->results()->first()->warnings[0])->toContain('page-scoped widget asset');

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

it('previews and approves bulk changes through the artisan command with json output', function (): void {
    $layout = bulkLayout(['main' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1], ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]]]);
    $specPath = sys_get_temp_dir() . '/testing-layout-bulk-change.json';
    File::put($specPath, json_encode(['criteria' => ['layout_keys' => [$layout->key]], 'operation' => ['type' => LayoutBulkWidgetOperationType::MoveWidget->value, 'source_widget_key' => 'breadcrumbs', 'target_widget_key' => 'hero', 'placement' => 'after']], JSON_THROW_ON_ERROR));

    $this->artisan('capell:layouts:bulk-change', ['--spec' => $specPath, '--preview' => true, '--json' => true])->assertSuccessful();
    $run = LayoutBulkChangeRun::query()->latest('id')->firstOrFail();
    $this->artisan('capell:layouts:bulk-change', ['--approve' => $run->uuid, '--json' => true])->assertSuccessful();

    expect(array_column($layout->fresh()->containers['main']['widgets'], 'widget_key'))->toBe(['hero', 'breadcrumbs']);
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
