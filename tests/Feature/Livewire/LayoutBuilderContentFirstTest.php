<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Widget;
use Capell\Core\Models\WidgetAsset;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('uses the content first editor mode by default from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_empty'));
});

it('can switch from content first to advanced layout and back from the package namespace', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('showAdvancedLayout', 'main:0:1:page:1:0')
        ->assertSet('editorMode', 'layout_first')
        ->assertSee(__('capell-layout-builder::button.return_to_content'))
        ->call('showContentEditor')
        ->assertSet('editorMode', 'content_first')
        ->assertSet('returnToContentItemKey', 'main:0:1:page:1:0');
});

it('lets content editors use content first without advanced layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertDontSee(__('capell-layout-builder::button.advanced_layout'));

    $component
        ->call('showAdvancedLayout')
        ->assertForbidden();
});

it('lets content editors submit widget asset edits without layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');
    Permission::findOrCreate('View:Page');

    test()->actingAs(test()->createUserWithPermission(['EditContent:Layout', 'View:Page']));

    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editWidgetAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => 'page',
        ])
        ->assertHasNoActionErrors();

    expect($widgetAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});

it('sends layout only editors straight to the advanced layout editor from the package namespace', function (): void {
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditLayout:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'layout_first')
        ->assertSee(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]))
        ->assertDontSee(__('capell-layout-builder::generic.content_first_editor'));
});

it('rejects stale content first asset saves from the package namespace', function (): void {
    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editWidgetAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => 'page',
            'contentInventorySignature' => 'stale-signature',
        ])
        ->assertNotified(__('capell-layout-builder::message.content_stale'));

    expect($widgetAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});
