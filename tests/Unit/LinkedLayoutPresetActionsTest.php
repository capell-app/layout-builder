<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\CreateLinkedLayoutPresetAction;
use Capell\LayoutBuilder\Actions\LinkLayoutPresetContainerAction;
use Capell\LayoutBuilder\Actions\RunLinkedLayoutPresetSyncAction;
use Capell\LayoutBuilder\Actions\SyncLayoutPresetUsagesAction;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncRunStatus;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Illuminate\Support\Facades\Queue;

it('creates a linked preset with immutable items and no embedded link marker', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'meta' => ['preset' => ['preset_id' => 999, 'preset_item_id' => 'obsolete', 'key' => 'obsolete', 'locked' => true]],
                'widgets' => [['widget_key' => 'hero', 'occurrence' => 1, 'meta' => ['content' => ['heading' => 'Keep this']]]],
            ],
            'sidebar' => ['widgets' => [['widget_key' => 'navigation', 'occurrence' => 1]]],
        ],
    ]);

    $preset = CreateLinkedLayoutPresetAction::run(
        layout: $layout,
        site: $site,
        containerKeys: ['main', 'sidebar'],
        name: 'Shared content',
    );

    $items = capell_test_array($preset->snapshot['items'] ?? null);

    expect($preset->mode)->toBe(LayoutPresetMode::Linked)
        ->and($preset->revision)->toBe(1)
        ->and($items)->toHaveCount(2)
        ->and($items[0]['id'])->toBeString()->not->toBeEmpty()
        ->and(data_get($items[0], 'container.meta.preset'))->toBeNull()
        ->and(data_get($items[0], 'container.widgets.0.meta.content.heading'))->toBe('Keep this');
});

it('projects linked container markers into exact usage rows', function (): void {
    $site = Site::factory()->create();
    $sourceLayout = Layout::factory()->site($site)->create(['containers' => ['main' => ['widgets' => []]]]);
    $preset = CreateLinkedLayoutPresetAction::run($sourceLayout, $site, ['main'], 'Shared main');
    $itemId = (string) data_get($preset->snapshot, 'items.0.id');

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'consumer' => LinkLayoutPresetContainerAction::run(
                ['widgets' => []],
                new LayoutPresetLinkData((int) $preset->getKey(), $itemId, $preset->key),
            ),
        ],
    ]);

    SyncLayoutPresetUsagesAction::run($layout);

    $usage = LayoutPresetUsage::query()->where('layout_id', $layout->getKey())->first();
    $usage = capell_test_instance($usage, LayoutPresetUsage::class);

    expect($usage->preset_id)->toBe((int) $preset->getKey())
        ->and($usage->preset_item_id)->toBe($itemId)
        ->and($usage->container_key)->toBe('consumer');
});

it('propagates one linked item while preserving the consumer container key', function (): void {
    Queue::fake();

    $site = Site::factory()->create();
    $sourceLayout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['widgets' => [['widget_key' => 'hero', 'occurrence' => 1]]]],
    ]);
    $preset = CreateLinkedLayoutPresetAction::run($sourceLayout, $site, ['main'], 'Shared main');
    $itemId = (string) data_get($preset->snapshot, 'items.0.id');
    $consumerLayout = Layout::factory()->site($site)->create([
        'containers' => [
            'landing-main' => LinkLayoutPresetContainerAction::run(
                ['widgets' => [['widget_key' => 'legacy', 'occurrence' => 1]]],
                new LayoutPresetLinkData((int) $preset->getKey(), $itemId, $preset->key),
            ),
        ],
    ]);
    SyncLayoutPresetUsagesAction::run($consumerLayout);

    $run = LayoutPresetSyncRun::query()->create([
        'preset_id' => $preset->getKey(),
        'revision' => $preset->revision,
        'status' => LayoutPresetSyncRunStatus::Queued,
        'summary' => [],
    ]);

    RunLinkedLayoutPresetSyncAction::run($run);

    $consumerLayout->refresh();
    $run->refresh();

    expect($consumerLayout->containers)->toHaveKey('landing-main')
        ->and($consumerLayout->containers['landing-main']['widgets'][0]['widget_key'])->toBe('hero')
        ->and(data_get($consumerLayout->containers['landing-main'], 'meta.preset.preset_item_id'))->toBe($itemId)
        ->and($run->status)->toBe(LayoutPresetSyncRunStatus::Completed);
});
