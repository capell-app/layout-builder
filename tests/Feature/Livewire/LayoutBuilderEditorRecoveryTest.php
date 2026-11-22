<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('opens an editor by persisted ids and selects the requested widget occurrence', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
            'sidebar' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 2]]],
        ],
    ]);
    $page = Page::factory()->create(['site_id' => $site->getKey(), 'layout_id' => $layout->getKey()]);

    Livewire::test(LayoutBuilder::class, [
        'layoutId' => $layout->getKey(),
        'siteId' => $site->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
        'initialContainerKey' => 'sidebar',
        'initialWidgetIndex' => 0,
    ])
        ->assertSet('selectedContainerKey', 'sidebar')
        ->assertSet('selectedWidgetIndex', 0)
        ->call('selectWidget', 'sidebar', 99)
        ->assertSet('selectedWidgetIndex', 0)
        ->call('selectContainer', 'main')
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', null)
        ->call('selectContainer', 'missing')
        ->assertSet('selectedContainerKey', 'main');
});

it('forbids an editor opened by id from using another site’s layout', function (): void {
    $site = Site::factory()->create();
    $foreign = Site::factory()->create();
    $foreignLayout = Layout::factory()->site($foreign)->create();

    Livewire::test(LayoutBuilder::class, [
        'layoutId' => $foreignLayout->getKey(),
        'siteId' => $site->getKey(),
    ])->assertForbidden();
});

it('preserves pending edits when a starter preset key is no longer registered', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => ['main' => ['widgets' => []]]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('addWidgetsToContainer', 'main', [$widget->getKey()])
        ->call('applyStarterLayoutPreset', 'removed-preset')
        ->assertNotified(Notification::make('starter-layout-preset-missing')
            ->body(__('capell-layout-builder::message.starter_layout_preset_not_found'))
            ->warning())
        ->call('saveLayout');

    expect(data_get($layout->refresh()->containers, 'main.widgets.0.widget_key'))->toBe($widget->key);
});

it('rejects a preset that belongs exclusively to another site', function (): void {
    $site = Site::factory()->create();
    $foreign = Site::factory()->create();
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => ['main' => ['widgets' => []]]]);
    $preset = LayoutPreset::factory()->for($foreign, 'site')->create([
        'name' => 'Foreign hero preset',
        'snapshot' => ['containers' => ['hero' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]],
    ]);

    Livewire::test(LayoutBuilder::class, ['layoutId' => $layout->getKey()])
        ->call('insertLayoutPreset', $preset->name, 'main')
        ->assertNotified(Notification::make('layout-preset-missing')
            ->body(__('capell-layout-builder::message.layout_preset_not_found'))
            ->warning())
        ->call('saveLayout');

    expect(array_keys((array) $layout->refresh()->containers))->toBe(['main']);
});

it('reports an invalid saved preset and retains the current layout', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => ['main' => ['widgets' => []]]]);
    $preset = LayoutPreset::factory()->for($site, 'site')->create(['snapshot' => ['containers' => []]]);

    Livewire::test(LayoutBuilder::class, ['layoutId' => $layout->getKey()])
        ->call('insertLayoutPreset', $preset->name, 'main')
        ->assertNotified(Notification::make('layout-preset-invalid')
            ->body(__('capell-layout-builder::message.layout_preset_invalid'))
            ->warning())
        ->call('saveLayout');

    expect(array_keys((array) $layout->refresh()->containers))->toBe(['main']);
});

it('inserts a picker selection together at the requested position and closes its own modal', function (): void {
    $site = Site::factory()->create();
    $first = Widget::factory()->create();
    $last = Widget::factory()->create();
    $chosenOne = Widget::factory()->create();
    $chosenTwo = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $first->key, 'occurrence' => 1],
                ['widget_key' => $last->key, 'occurrence' => 1],
            ]],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layoutId' => $layout->getKey()])
        ->call('addWidgetsToContainer', 'main', [$chosenOne->getKey(), $chosenTwo->getKey()], 'choose-banners', 1)
        ->assertDispatched('close-modal', id: 'choose-banners')
        ->call('saveLayout');

    $widgets = data_get($layout->refresh()->containers, 'main.widgets');
    expect(array_column(capell_test_array($widgets), 'widget_key'))->toBe([
        $first->key,
        $chosenOne->key,
        $chosenTwo->key,
        $last->key,
    ]);
});

it('rejects replayed positional edits from a lost draft and lets a fresh editor save', function (): void {
    $site = Site::factory()->create();
    $header = Widget::factory()->create();
    $footer = Widget::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => [
            'meta' => ['editorFixture' => str_repeat('draft', 27000)],
            'widgets' => [
                ['widget_key' => $header->key, 'occurrence' => 1],
                ['widget_key' => $footer->key, 'occurrence' => 1],
            ],
        ]],
    ]);

    $editor = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('moveWidgetDown', 'main', 0);
    $replay = clone $editor;

    Session::forget(sprintf('capell-layout-builder:%s:layout-state', $editor->id()));

    config(['app.debug' => false]);
    $editor->call('removeWidget', 'main', 0)
        ->__call('assertStatus', [409])
        ->__call('assertHeader', ['Content-Type', 'text/plain; charset=UTF-8'])
        ->__call('assertContent', [__('capell-layout-builder::message.layout_draft_expired')]);
    $replay->call('removeWidget', 'main', 0)->__call('assertStatus', [409]);
    expect(array_column(capell_test_array(data_get($layout->refresh()->containers, 'main.widgets')), 'widget_key'))
        ->toBe([$header->key, $footer->key]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('removeWidget', 'main', 1)
        ->call('saveLayout')
        ->assertOk();

    expect(array_column(capell_test_array(data_get($layout->refresh()->containers, 'main.widgets')), 'widget_key'))
        ->toBe([$header->key]);
});
