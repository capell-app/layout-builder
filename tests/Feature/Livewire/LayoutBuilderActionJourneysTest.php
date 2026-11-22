<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('detaches a linked container without losing its widget or mutating the shared preset', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
    ]);

    $builder = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLinkedLayoutPreset', ['main'], 'Shared lead')
        ->call('saveLayout');
    $preset = LayoutPreset::query()->where('name', 'Shared lead')->firstOrFail();
    $snapshot = $preset->snapshot;

    expect(data_get($layout->refresh()->containers, 'main.meta.preset.preset_id'))->toBe($preset->getKey());

    $builder->callAction('detachContainerFromPreset', arguments: ['containerKey' => 'main'])
        ->assertHasNoActionErrors()
        ->assertSet('containers.main.widgets.0.widget_key', $widget->key)
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'main.meta.preset'))->toBeNull()
        ->and(data_get($layout->containers, 'main.widgets.0.widget_key'))->toBe($widget->key)
        ->and($preset->refresh()->snapshot)->toBe($snapshot);
});

it('saves a new container at the requested position without dropping existing layout content', function (): void {
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
            'footer' => ['widgets' => []],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('addContainer', data: ['key' => 'sidebar', 'meta' => ['area' => 'main']], arguments: ['position' => 1])
        ->assertHasNoActionErrors()
        ->assertSet('containers.sidebar.meta.area', 'main')
        ->callAction('saveLayout')
        ->assertDispatched('capell-layout-builder-authoring-saved');

    $containers = $layout->refresh()->containers;
    expect(array_keys($containers))->toBe(['main', 'sidebar', 'footer'])
        ->and(data_get($containers, 'main.widgets.0.widget_key'))->toBe($widget->key);
});

it('publishes a linked container edit as a new preset revision and queues its propagation', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['meta' => ['area' => 'main', 'html_class' => 'old-style'], 'widgets' => []]],
    ]);
    $builder = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLinkedLayoutPreset', ['main'], 'Shared style')
        ->call('saveLayout');

    $preset = LayoutPreset::query()->where('name', 'Shared style')->firstOrFail();
    $revision = $preset->revision;

    $builder->callAction('editLinkedContainer', data: [
        'key' => 'main',
        'meta' => ['area' => 'main', 'html_class' => 'new-style'],
    ], arguments: ['containerKey' => 'main'])->assertHasNoActionErrors();

    expect(data_get($layout->refresh()->containers, 'main.meta.html_class'))->toBe('new-style')
        ->and($preset->refresh()->revision)->toBe($revision + 1)
        ->and(data_get($preset->snapshot, 'items.0.container.meta.html_class'))->toBe('new-style');

    $propagation = LayoutPresetSyncRun::query()->where('preset_id', $preset->getKey())->sole();
    expect($propagation->revision)->toBe($preset->revision);
});

it('preserves a newer layout when a stale editor submits the save action', function (): void {
    $layout = Layout::factory()->create([
        'containers' => ['main' => ['widgets' => []]],
        'updated_at' => now()->subMinute(),
    ]);
    $builder = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('addContainer', data: ['key' => 'stale-draft', 'meta' => ['area' => 'main']])
        ->assertHasNoActionErrors();

    $latest = ['main' => ['widgets' => []], 'other-editor' => ['widgets' => []]];
    $layout->update(['containers' => $latest]);

    $builder->callAction('saveLayout')
        ->assertHasErrors('layoutConflict')
        ->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($layout->refresh()->containers)->toBe($latest);
});
