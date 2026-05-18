<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\AdminElementPreviewData;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('uses the content first editor mode by default from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_empty'));
});

it('resolves the saved admin element preview view without checking the filesystem', function (): void {
    $previewData = new AdminElementPreviewData(
        view: 'capell-layout-builder::filament.layout-builder.previews.custom',
        label: 'Custom preview',
        title: null,
        excerpt: null,
        image: null,
        typeLabel: null,
        icon: null,
        assetCount: 0,
        hasPageAssets: false,
        usesPageContent: false,
    );

    $component = new LayoutBuilder;

    expect($component->resolveAdminElementPreviewView($previewData))
        ->toBe('capell-layout-builder::filament.layout-builder.previews.custom');
});

it('can switch from content first to advanced layout and back from the package namespace', function (): void {
    $element = Element::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => [
            ['element_key' => $element->key, 'occurrence' => 1],
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
        'main' => ['elements' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertDontSee(__('capell-layout-builder::button.advanced_layout'));

    $component
        ->call('showAdvancedLayout')
        ->assertForbidden();
});

it('lets content editors submit element asset edits without layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');
    Permission::findOrCreate('View:Page');

    test()->actingAs(test()->createUserWithPermission(['EditContent:Layout', 'View:Page']));

    $element = Element::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $elementAsset = ElementAsset::factory()
        ->element($element)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => [
            ['element_key' => $element->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editElementAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'elementIndex' => 0,
            'index' => 0,
            'type' => 'page',
        ])
        ->assertHasNoActionErrors();

    expect($elementAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});

it('renders content first rows as custom action triggers instead of per row action schemas from the package namespace', function (): void {
    $element = Element::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    ElementAsset::factory()
        ->element($element)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => [
            ['element_key' => $element->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::form.search_content_inventory'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_search_empty'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_search_hint'))
        ->assertSeeHtml('data-layout-content-search-input')
        ->assertSeeHtml('data-layout-content-search-empty')
        ->assertSeeHtml('data-layout-content-search=')
        ->assertSeeHtml('data-layout-content-action="editElementAsset"')
        ->assertSeeHtml('$wire.mountAction(\'editElementAsset\'')
        ->assertSeeHtml('wire:key="layout-content-group-')
        ->assertSeeHtml('wire:key="layout-content-item-');

    $contentFirstView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/content-first.blade.php');
    $assetRowView = file_get_contents(__DIR__ . '/../../../resources/views/components/filament/layout-builder/asset.blade.php');

    expect($contentFirstView)
        ->not->toContain('$this->editElementAssetAction')
        ->and($assetRowView)
        ->not->toContain('$this->editElementAssetAction');
});

it('sends layout only editors straight to the advanced layout editor from the package namespace', function (): void {
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditLayout:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'layout_first')
        ->assertSee(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]))
        ->assertDontSee(__('capell-layout-builder::generic.content_first_editor'));
});

it('moves responsive layout mutations through undo and redo stacks from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'elements' => [],
            'meta' => [
                'responsive' => [
                    'tablet' => ['colspan' => 6],
                ],
            ],
        ],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'tablet')
        ->call('resetResponsiveContainerOverride', 'main');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);

    $component->call('undoLayoutMutation');

    expect($component->get('containers')['main']['meta']['responsive']['tablet']['colspan'])->toBe(6)
        ->and($component->get('layoutUndoSnapshots'))->toBe([])
        ->and($component->get('layoutRedoSnapshots'))->toHaveCount(1);

    $component->call('redoLayoutMutation');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);
});

it('clears redo history when a new layout mutation follows undo from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'elements' => [],
            'meta' => [
                'responsive' => [
                    'tablet' => ['colspan' => 6],
                ],
            ],
        ],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'tablet')
        ->call('resetResponsiveContainerOverride', 'main')
        ->call('undoLayoutMutation')
        ->call('resetResponsiveContainerOverride', 'main');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);
});

it('blocks content only editors from layout undo and redo from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => []],
    ]]);

    $snapshot = [
        'containers' => ['main' => ['elements' => [], 'meta' => []]],
        'assets' => ['main' => []],
        'originalAssets' => ['main' => []],
        'selectedRecords' => ['main' => []],
    ];

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('layoutUndoSnapshots', [$snapshot])
        ->call('undoLayoutMutation')
        ->assertForbidden();

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('layoutRedoSnapshots', [$snapshot])
        ->call('redoLayoutMutation')
        ->assertForbidden();
});

it('rejects stale content first asset saves from the package namespace', function (): void {
    $element = Element::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $elementAsset = ElementAsset::factory()
        ->element($element)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['elements' => [
            ['element_key' => $element->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editElementAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'elementIndex' => 0,
            'index' => 0,
            'type' => 'page',
            'contentInventorySignature' => 'stale-signature',
        ])
        ->assertNotified(__('capell-layout-builder::message.content_stale'));

    expect($elementAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});
