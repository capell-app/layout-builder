<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('restores copied widgets and undo history across large editor requests', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['meta' => ['editorFixture' => str_repeat('draft', 27000)], 'widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
            'sidebar' => ['widgets' => []],
        ],
    ]);

    $editor = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('copyLayoutWidget', 'main', 0)
        ->call('pasteLayoutFragment', 'sidebar')
        ->call('undoLayoutMutation')
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'main.widgets.0.widget_key'))->toBe($widget->key)
        ->and(data_get($layout->containers, 'sidebar.widgets'))->toBeEmpty();

    $editor->call('pasteLayoutFragment', 'sidebar')
        ->call('undoLayoutMutation')
        ->call('redoLayoutMutation')
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'sidebar.widgets.0.widget_key'))->toBe($widget->key);
});

it('copies a container without changing the original when its pasted copy is edited', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('copyLayoutContainer', 'main')
        ->call('pasteLayoutFragment', 'main')
        ->assertSet('containers.main-copy.widgets.0.widget_key', $widget->key)
        ->call('saveContainer', ['key' => 'main-copy', 'meta' => ['colspan' => 4]], 'main-copy')
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'main-copy.meta.colspan'))->toBe(4)
        ->and(data_get($layout->containers, 'main.meta.colspan'))->not->toBe(4)
        ->and(data_get($layout->containers, 'main.widgets.0.widget_key'))->toBe($widget->key);
});

it('detaches one linked layout preset placement while retaining its copied content', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
    ]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLinkedLayoutPreset', ['main'], 'Shared header');
    $preset = LayoutPreset::query()->where('name', 'Shared header')->firstOrFail();
    $component->assertSet('containers.main.meta.preset.preset_id', $preset->getKey())
        ->call('insertLayoutPreset', 'Shared header', 'main')
        ->assertSet('containers.main-2.widgets.0.widget_key', $widget->key)
        ->call('detachContainerFromPreset', 'main-2')
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'main.meta.preset.preset_id'))->toBe($preset->getKey())
        ->and(data_get($layout->containers, 'main-2.meta.preset'))->toBeNull()
        ->and(data_get($layout->containers, 'main-2.widgets.0.widget_key'))->toBe($widget->key);
});

it('preserves the existing named preset when an editor tries to overwrite it', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
            'sidebar' => ['widgets' => []],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLayoutPreset', 'main', 'Reusable banner')
        ->call('saveLayoutPreset', 'sidebar', 'Reusable banner');

    $presets = LayoutPreset::query()->where('name', 'Reusable banner')->get();

    expect($presets)->toHaveCount(1)
        ->and(data_get($presets->first(), 'snapshot.containers.main.widgets.0.widget_key'))->toBe($widget->key);
});

it('preserves the saved layout if an expired editor draft tries to save', function (): void {
    $site = Site::factory()->create();
    $saved = Widget::factory()->create();
    $draft = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['meta' => ['editorFixture' => str_repeat('draft', 27000)], 'widgets' => [['widget_key' => $saved->key, 'occurrence' => 1]]],
        ],
    ]);

    $editor = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('addWidgetsToContainer', 'main', [$draft->getKey()]);

    Session::forget(sprintf('capell-layout-builder:%s:layout-state', $editor->id()));

    $editor->call('saveLayout')->__call('assertStatus', [409]);
    expect(data_get($layout->refresh()->containers, 'main.widgets'))->toHaveCount(1)
        ->and(data_get($layout->containers, 'main.widgets.0.widget_key'))->toBe($saved->key);
});

it('schedules the new linked preset revision and excludes the placement already saved by the editor', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['meta' => ['colspan' => 12], 'widgets' => []]],
    ]);

    $editor = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLinkedLayoutPreset', ['main'], 'Shared hero')
        ->call('saveLayout');
    $preset = LayoutPreset::query()->where('name', 'Shared hero')->firstOrFail();
    $source = LayoutPresetUsage::query()->where('preset_id', $preset->getKey())->where('layout_id', $layout->getKey())->firstOrFail();

    $editor->call('saveLinkedContainer', [
        'key' => 'main',
        'meta' => ['colspan' => 4, 'preset' => data_get($layout->refresh()->containers, 'main.meta.preset')],
    ], 'main')->assertHasNoErrors();

    $run = LayoutPresetSyncRun::query()->where('preset_id', $preset->getKey())->firstOrFail();

    expect(data_get($layout->refresh()->containers, 'main.meta.colspan'))->toBe(4)
        ->and(data_get($preset->refresh()->snapshot, 'items.0.container.meta.colspan'))->toBe(4)
        ->and($run->revision)->toBe($preset->revision)
        ->and($run->excluded_usage_id)->toBe($source->getKey());
});
